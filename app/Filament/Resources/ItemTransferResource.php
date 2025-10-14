<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemTransferResource\Pages;
use App\Models\ItemTransfer;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemTransferResource extends Resource
{
    protected static ?string $model = ItemTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $modelLabel = 'Transferencia';

    protected static ?string $pluralModelLabel = 'Transferencias de Artículos';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Transferencia')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('transfer_number')
                                    ->label('Número de Transferencia')
                                    ->required()
                                    ->default(fn () => ItemTransfer::generateTransferNumber())
                                    ->disabled()
                                    ->dehydrated()
                                    ->unique(ignoreRecord: true),

                                Forms\Components\DatePicker::make('transfer_date')
                                    ->label('Fecha de Transferencia')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('d/m/Y')
                                    ->maxDate(now()),
                            ]),
                    ]),

                Forms\Components\Section::make('Detalles del Artículo')
                    ->schema([
                        Forms\Components\Select::make('item_id')
                            ->label('Artículo')
                            ->relationship('item', 'name', function (Builder $query) {
                                return $query->where('status', 'available')
                                    ->with(['branch', 'category']);
                            })
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return $record->name . ' - ' . ($record->category?->name ?? 'Sin categoría') . ' ($' . number_format($record->appraised_value, 2) . ')';
                            })
                            ->searchable(['name', 'description'])
                            ->required()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Forms\Set $set, $state) {
                                if ($state) {
                                    $item = Item::find($state);
                                    if ($item) {
                                        $set('from_branch_id', $item->branch_id);
                                    }
                                }
                            })
                            ->helperText('Solo se muestran artículos disponibles'),

                        Forms\Components\Placeholder::make('item_details')
                            ->label('Información del Artículo')
                            ->content(function (Get $get) {
                                $itemId = $get('item_id');
                                if (!$itemId) {
                                    return 'Seleccione un artículo para ver los detalles';
                                }

                                $item = Item::with(['branch', 'category'])->find($itemId);
                                if (!$item) {
                                    return 'Artículo no encontrado';
                                }

                                return sprintf(
                                    'Categoría: %s | Valor: $%s | Estado: %s | Sucursal Actual: %s',
                                    $item->category?->name ?? 'Sin categoría',
                                    number_format($item->appraised_value, 2),
                                    $item->status,
                                    $item->branch->name
                                );
                            })
                            ->hidden(fn (Get $get) => !$get('item_id')),
                    ]),

                Forms\Components\Section::make('Sucursales')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('from_branch_id')
                                    ->label('Sucursal Origen')
                                    ->relationship('fromBranch', 'name')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->preload()
                                    ->helperText('Se establece automáticamente según el artículo'),

                                Forms\Components\Select::make('to_branch_id')
                                    ->label('Sucursal Destino')
                                    ->relationship('toBranch', 'name')
                                    ->required()
                                    ->preload()
                                    ->searchable()
                                    ->different('from_branch_id')
                                    ->validationMessages([
                                        'different' => 'La sucursal destino debe ser diferente a la sucursal origen',
                                    ])
                                    ->helperText('Seleccione la sucursal a la que se transferirá el artículo'),
                            ]),
                    ]),

                Forms\Components\Section::make('Estado y Notas')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->required()
                                    ->options([
                                        'pending' => 'Pendiente',
                                        'in_transit' => 'En Tránsito',
                                        'received' => 'Recibido',
                                        'cancelled' => 'Cancelado',
                                    ])
                                    ->default('pending')
                                    ->native(false),

                                Forms\Components\DatePicker::make('received_date')
                                    ->label('Fecha de Recepción')
                                    ->displayFormat('d/m/Y')
                                    ->visible(fn (Get $get) => $get('status') === 'received'),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Información adicional sobre la transferencia'),

                        Forms\Components\Hidden::make('transferred_by')
                            ->default(auth()->id()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('item.name')
                    ->label('Artículo')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ItemTransfer $record): string => $record->item->category?->name ?? ''),

                Tables\Columns\TextColumn::make('fromBranch.name')
                    ->label('Origen')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\IconColumn::make('direction')
                    ->label('')
                    ->icon('heroicon-o-arrow-right')
                    ->color('primary')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('toBranch.name')
                    ->label('Destino')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('transfer_date')
                    ->label('Fecha de Envío')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('received_date')
                    ->label('Fecha de Recepción')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_transit' => 'info',
                        'received' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'in_transit' => 'En Tránsito',
                        'received' => 'Recibido',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('transferredBy.name')
                    ->label('Transferido Por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('receivedBy.name')
                    ->label('Recibido Por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'in_transit' => 'En Tránsito',
                        'received' => 'Recibido',
                        'cancelled' => 'Cancelado',
                    ]),

                Tables\Filters\SelectFilter::make('from_branch_id')
                    ->label('Sucursal Origen')
                    ->relationship('fromBranch', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('to_branch_id')
                    ->label('Sucursal Destino')
                    ->relationship('toBranch', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\Filter::make('transfer_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transfer_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transfer_date', '<=', $date),
                            );
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (ItemTransfer $record): bool => in_array($record->status, ['pending', 'in_transit'])),

                // Marcar como En Tránsito
                Tables\Actions\Action::make('mark_in_transit')
                    ->label('Marcar En Tránsito')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->visible(fn (ItemTransfer $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Marcar como En Tránsito')
                    ->modalDescription('¿Confirma que el artículo ha sido enviado?')
                    ->modalSubmitActionLabel('Sí, Marcar En Tránsito')
                    ->action(function (ItemTransfer $record): void {
                        $record->markAsInTransit();

                        Notification::make()
                            ->success()
                            ->title('Transferencia Actualizada')
                            ->body("La transferencia {$record->transfer_number} ha sido marcada como en tránsito.")
                            ->send();
                    }),

                // Marcar como Recibido
                Tables\Actions\Action::make('mark_received')
                    ->label('Marcar Recibido')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ItemTransfer $record): bool => in_array($record->status, ['pending', 'in_transit']))
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Recepción')
                    ->modalDescription('¿Confirma que el artículo ha sido recibido en la sucursal destino? Esto actualizará la sucursal del artículo automáticamente.')
                    ->modalSubmitActionLabel('Sí, Confirmar Recepción')
                    ->action(function (ItemTransfer $record): void {
                        \DB::transaction(function () use ($record) {
                            $record->markAsReceived(auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Transferencia Completada')
                                ->body("El artículo {$record->item->name} ha sido transferido exitosamente a {$record->toBranch->name}.")
                                ->send();
                        });
                    }),

                // Cancelar Transferencia
                Tables\Actions\Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ItemTransfer $record): bool => in_array($record->status, ['pending', 'in_transit']))
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar Transferencia')
                    ->modalDescription('¿Está seguro de que desea cancelar esta transferencia?')
                    ->modalSubmitActionLabel('Sí, Cancelar')
                    ->action(function (ItemTransfer $record): void {
                        $record->cancel();

                        Notification::make()
                            ->warning()
                            ->title('Transferencia Cancelada')
                            ->body("La transferencia {$record->transfer_number} ha sido cancelada.")
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('transfer_date', 'desc');
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
            'index' => Pages\ListItemTransfers::route('/'),
            'create' => Pages\CreateItemTransfer::route('/create'),
            'view' => Pages\ViewItemTransfer::route('/{record}'),
            'edit' => Pages\EditItemTransfer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')
            ->orWhere('status', 'in_transit')
            ->count() ?: null;
    }
}
