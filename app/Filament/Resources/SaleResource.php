<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Operaciones';

    protected static ?string $modelLabel = 'Venta';

    protected static ?string $pluralModelLabel = 'Ventas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Venta')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('sale_number')
                                    ->label('Número de Venta')
                                    ->required()
                                    ->default(fn () => Sale::generateSaleNumber())
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\Select::make('item_id')
                                    ->label('Artículo')
                                    ->relationship('item', 'name', fn (Builder $query) =>
                                        $query->whereIn('status', ['Disponible', 'Confiscado'])
                                    )
                                    ->searchable()
                                    ->required()
                                    ->preload(),
                                Forms\Components\Select::make('customer_id')
                                    ->label('Cliente')
                                    ->relationship('customer', 'first_name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                                    ->searchable(['first_name', 'last_name'])
                                    ->preload()
                                    ->helperText('Opcional - dejar vacío para venta sin cliente registrado'),
                                Forms\Components\Select::make('branch_id')
                                    ->label('Sucursal')
                                    ->relationship('branch', 'name')
                                    ->preload()
                                    ->required()
                                    ->searchable()
                                    ->helperText('Sucursal donde se registra la venta'),
                            ]),
                    ]),

                Forms\Components\Section::make('Precios')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('sale_price')
                                    ->label('Precio de Venta')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::calculateFinalPrice($get, $set);
                                    }),
                                Forms\Components\TextInput::make('discount')
                                    ->label('Descuento')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::calculateFinalPrice($get, $set);
                                    }),
                                Forms\Components\TextInput::make('final_price')
                                    ->label('Precio Final')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ]),

                Forms\Components\Section::make('Detalles de Pago y Entrega')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('sale_date')
                                    ->label('Fecha de Venta')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('d/m/Y'),
                                Forms\Components\Select::make('payment_method')
                                    ->label('Método de Pago')
                                    ->required()
                                    ->options([
                                        'Efectivo' => 'Efectivo',
                                        'Transferencia' => 'Transferencia',
                                        'Tarjeta de Débito' => 'Tarjeta de Débito',
                                        'Tarjeta de Crédito' => 'Tarjeta de Crédito',
                                        'Cheque' => 'Cheque',
                                        'Otro' => 'Otro',
                                    ])
                                    ->default('Efectivo')
                                    ->native(false),
                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->required()
                                    ->options([
                                        'Completada' => 'Completada',
                                        'Pendiente' => 'Pendiente',
                                        'Cancelada' => 'Cancelada',
                                    ])
                                    ->default('Completada')
                                    ->native(false),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('Número de Factura')
                                    ->maxLength(100),
                                Forms\Components\DatePicker::make('delivery_date')
                                    ->label('Fecha de Entrega')
                                    ->displayFormat('d/m/Y'),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function calculateFinalPrice(Get $get, Set $set): void
    {
        $salePrice = (float) $get('sale_price') ?: 0;
        $discount = (float) $get('discount') ?: 0;

        $finalPrice = $salePrice - $discount;
        $finalPrice = max(0, $finalPrice); // No permitir valores negativos

        $set('final_price', round($finalPrice, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Artículo')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Cliente')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->default('Sin Cliente')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Precio')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount')
                    ->label('Descuento')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('final_price')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completada' => 'success',
                        'Pendiente' => 'warning',
                        'Cancelada' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Sucursal')
                    ->relationship('branch', 'name')
                    ->preload()
                    ->searchable()
                    ->visible(fn () => auth()->user()->can('view_all_branches')),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Imprimir Recibo
                Tables\Actions\Action::make('imprimir_recibo')
                    ->label('Imprimir Recibo')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Sale $record): string => route('pdf.sale-receipt', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('sale_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
