<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Artículos';

    protected static ?string $recordTitleAttribute = 'name';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información General')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Nombre'),
                                Infolists\Components\TextEntry::make('category.name')
                                    ->label('Categoría')
                                    ->badge()
                                    ->color(fn ($record): string => $record->category?->color ?? 'gray'),
                                Infolists\Components\TextEntry::make('condition')
                                    ->label('Condición')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'excellent' => 'Excelente',
                                        'good' => 'Bueno',
                                        'fair' => 'Regular',
                                        'poor' => 'Malo',
                                        default => $state,
                                    }),
                                Infolists\Components\TextEntry::make('brand')
                                    ->label('Marca'),
                                Infolists\Components\TextEntry::make('model')
                                    ->label('Modelo'),
                                Infolists\Components\TextEntry::make('serial_number')
                                    ->label('Número de Serie'),
                                Infolists\Components\TextEntry::make('location')
                                    ->label('Ubicación'),
                                Infolists\Components\TextEntry::make('branch.name')
                                    ->label('Sucursal')
                                    ->badge()
                                    ->color('info'),
                            ]),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\Section::make('Valuación')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('appraised_value')
                                    ->label('Valor Tasado')
                                    ->money('GTQ'),
                                Infolists\Components\TextEntry::make('market_value')
                                    ->label('Valor de Mercado')
                                    ->money('GTQ'),
                                Infolists\Components\TextEntry::make('purchase_price')
                                    ->label('Precio de Compra')
                                    ->money('GTQ'),
                                Infolists\Components\TextEntry::make('sale_price')
                                    ->label('Precio de Venta')
                                    ->money('GTQ'),
                            ]),
                    ]),
                Infolists\Components\Section::make('Estado')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'available' => 'Disponible',
                                        'collateral' => 'En Préstamo',
                                        'sold' => 'Vendido',
                                        'forfeited' => 'Confiscado',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'available' => 'success',
                                        'collateral' => 'warning',
                                        'sold' => 'info',
                                        'forfeited' => 'danger',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('acquired_date')
                                    ->label('Fecha de Adquisición')
                                    ->date('d/m/Y'),
                            ]),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function form(Form $form): Form
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
                                Forms\Components\Select::make('category_id')
                                    ->label('Categoría')
                                    ->relationship('category', 'name', function (Builder $query) {
                                        return $query->where('is_active', true)->ordered();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Forms\Components\Select::make('condition')
                                    ->label('Condición')
                                    ->required()
                                    ->options([
                                        'excellent' => 'Excelente',
                                        'good' => 'Bueno',
                                        'fair' => 'Regular',
                                        'poor' => 'Malo',
                                    ])
                                    ->default('good')
                                    ->native(false),
                                Forms\Components\TextInput::make('brand')
                                    ->label('Marca')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('model')
                                    ->label('Modelo')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('serial_number')
                                    ->label('Número de Serie')
                                    ->maxLength(100)
                                    ->unique(Item::class, 'serial_number', ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                        return $rule->whereNotNull('serial_number');
                                    }),
                                Forms\Components\TextInput::make('location')
                                    ->label('Ubicación')
                                    ->maxLength(100),
                                Forms\Components\Select::make('branch_id')
                                    ->label('Sucursal')
                                    ->relationship('branch', 'name')
                                    ->preload()
                                    ->required()
                                    ->searchable()
                                    ->default(auth()->user()->branch_id)
                                    ->helperText('Sucursal donde se registra el artículo'),
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
                                    ->prefix('Q')
                                    ->default(0),
                                Forms\Components\TextInput::make('market_value')
                                    ->label('Valor de Mercado')
                                    ->numeric()
                                    ->prefix('Q'),
                                Forms\Components\TextInput::make('purchase_price')
                                    ->label('Precio de Compra')
                                    ->numeric()
                                    ->prefix('Q'),
                                Forms\Components\TextInput::make('sale_price')
                                    ->label('Precio de Venta')
                                    ->numeric()
                                    ->prefix('Q'),
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
                                        'available' => 'Disponible',
                                        'collateral' => 'En Préstamo',
                                        'sold' => 'Vendido',
                                        'forfeited' => 'Confiscado',
                                    ])
                                    ->default('available')
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
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría')
                    ->badge()
                    ->color(fn ($record): string => $record->category?->color ?? 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Marca')
                    ->searchable(),
                Tables\Columns\TextColumn::make('appraised_value')
                    ->label('Valor Tasado')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'collateral' => 'En Préstamo',
                        'sold' => 'Vendido',
                        'forfeited' => 'Confiscado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'collateral' => 'warning',
                        'sold' => 'info',
                        'forfeited' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('acquired_date')
                    ->label('Fecha Adquisición')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'available' => 'Disponible',
                        'collateral' => 'En Préstamo',
                        'sold' => 'Vendido',
                        'forfeited' => 'Confiscado',
                    ])
                    ->multiple(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['customer_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('acquired_date', 'desc');
    }
}
