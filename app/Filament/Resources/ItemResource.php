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
                                Forms\Components\Select::make('category_id')
                                    ->label('Categoría')
                                    ->relationship('category', 'name', function (Builder $query) {
                                        return $query->where('is_active', true)->ordered();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                                        Forms\Components\TextInput::make('slug')
                                            ->label('Slug')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Activa')
                                            ->default(true),
                                    ])
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
                        // Forms\Components\FileUpload::make('photos')
                        //     ->label('Fotos del Artículo')
                        //     ->image()
                        //     ->multiple()
                        //     ->maxFiles(5)
                        //     ->maxSize(10240)
                        //     ->directory('items')
                        //     ->imagePreviewHeight('250')
                        //     ->panelLayout('grid')
                        //     ->reorderable()
                        //     ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
                        //     ->columnSpanFull()
                        //     ->helperText('Puede subir hasta 5 imágenes (máx. 10MB cada una). Formatos: JPG, PNG, WEBP'),
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
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record): string => $record->category?->color ?? 'gray'),
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
                        'available' => 'success',
                        'collateral' => 'warning',
                        'sold' => 'info',
                        'forfeited' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'collateral' => 'En Préstamo',
                        'sold' => 'Vendido',
                        'forfeited' => 'Confiscado',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Sucursal')
                    ->relationship('branch', 'name')
                    ->preload()
                    ->searchable()
                    ->visible(fn () => auth()->user()->can('view_all_branches')),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'available' => 'Disponible',
                        'collateral' => 'En Préstamo',
                        'sold' => 'Vendido',
                        'forfeited' => 'Confiscado',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name', function (Builder $query) {
                        return $query->where('is_active', true)->ordered();
                    })
                    ->preload()
                    ->searchable()
                    ->multiple(),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('update_status')
                        ->label('Cambiar Estado')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Nuevo Estado')
                                ->options([
                                    'available' => 'Disponible',
                                    'collateral' => 'En Préstamo',
                                    'sold' => 'Vendido',
                                    'forfeited' => 'Confiscado',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Estado actualizado')
                                ->body('El estado de los artículos seleccionados ha sido actualizado.')
                        ),
                    Tables\Actions\BulkAction::make('update_branch')
                        ->label('Cambiar Sucursal')
                        ->icon('heroicon-o-building-storefront')
                        ->form([
                            Forms\Components\Select::make('branch_id')
                                ->label('Nueva Sucursal')
                                ->relationship('branch', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['branch_id' => $data['branch_id']]);
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Sucursal actualizada')
                                ->body('Los artículos han sido transferidos a la nueva sucursal.')
                        ),
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
            RelationManagers\ActivitiesRelationManager::class,
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
