<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $modelLabel = 'Artículo';

    protected static ?string $pluralModelLabel = 'Artículos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(200)
                                    ->columnSpan(2),
                                Forms\Components\Select::make('category')
                                    ->label('Categoría')
                                    ->required()
                                    ->options([
                                        'Joyería' => 'Joyería',
                                        'Electrónica' => 'Electrónica',
                                        'Herramientas' => 'Herramientas',
                                        'Otros' => 'Otros',
                                    ])
                                    ->native(false),
                                Forms\Components\Select::make('condition')
                                    ->label('Condición')
                                    ->required()
                                    ->options([
                                        'Nuevo' => 'Nuevo',
                                        'Excelente' => 'Excelente',
                                        'Bueno' => 'Bueno',
                                        'Regular' => 'Regular',
                                        'Dañado' => 'Dañado',
                                    ])
                                    ->native(false),
                                Forms\Components\TextInput::make('brand')
                                    ->label('Marca')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('model')
                                    ->label('Modelo')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('serial_number')
                                    ->label('Número de Serie')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('location')
                                    ->label('Ubicación')
                                    ->maxLength(100),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Valuación')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('appraised_value')
                                    ->label('Valor Tasado')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0),
                                Forms\Components\TextInput::make('market_value')
                                    ->label('Valor de Mercado')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('purchase_price')
                                    ->label('Precio de Compra')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('sale_price')
                                    ->label('Precio de Venta')
                                    ->numeric()
                                    ->prefix('$'),
                            ]),
                    ]),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->required()
                                    ->options([
                                        'Disponible' => 'Disponible',
                                        'En Préstamo' => 'En Préstamo',
                                        'Vendido' => 'Vendido',
                                        'Confiscado' => 'Confiscado',
                                    ])
                                    ->default('Disponible')
                                    ->native(false),
                                Forms\Components\DatePicker::make('acquired_date')
                                    ->label('Fecha de Adquisición')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('d/m/Y'),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('photos')
                            ->label('Fotos del Artículo')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->directory('items')
                            ->imageEditor()
                            ->columnSpanFull()
                            ->helperText('Puede subir hasta 5 imágenes'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Joyería' => 'warning',
                        'Electrónica' => 'info',
                        'Herramientas' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Marca')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('appraised_value')
                    ->label('Valor Tasado')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Precio Venta')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Disponible' => 'success',
                        'En Préstamo' => 'warning',
                        'Vendido' => 'info',
                        'Confiscado' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
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
