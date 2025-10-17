<?php

namespace App\Filament\Resources\ItemTransferResource\Pages;

use App\Filament\Resources\ItemTransferResource;
use App\Models\ItemTransfer;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewItemTransfer extends ViewRecord
{
    protected static string $resource = ItemTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (ItemTransfer $record): bool => in_array($record->status, ['pending', 'in_transit'])),

            // Marcar como En Tránsito
            Actions\Action::make('mark_in_transit')
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

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
                }),

            // Marcar como Recibido
            Actions\Action::make('mark_received')
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

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
                }),

            // Cancelar Transferencia
            Actions\Action::make('cancel')
                ->label('Cancelar Transferencia')
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

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
                }),

            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información de la Transferencia')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('transfer_number')
                                ->label('Número de Transferencia')
                                ->weight('bold'),
                            Infolists\Components\TextEntry::make('status')
                                ->label('Estado')
                                ->badge()
                                ->formatStateUsing(fn (?string $state): string => match ($state) {
                                    'pending' => 'Pendiente',
                                    'in_transit' => 'En Tránsito',
                                    'received' => 'Recibido',
                                    'cancelled' => 'Cancelado',
                                    default => $state ?? 'N/A',
                                })
                                ->color(fn (?string $state): string => match ($state) {
                                    'pending' => 'warning',
                                    'in_transit' => 'info',
                                    'received' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('transfer_date')
                                ->label('Fecha de Transferencia')
                                ->date('d/m/Y H:i'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Artículo Transferido')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('item.name')
                                ->label('Nombre del Artículo')
                                ->weight('bold')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            Infolists\Components\TextEntry::make('item.category')
                                ->label('Categoría'),
                            Infolists\Components\TextEntry::make('item.appraised_value')
                                ->label('Valor Tasado')
                                ->money('GTQ'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Sucursales')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('fromBranch.name')
                                ->label('Sucursal Origen')
                                ->badge()
                                ->color('gray'),
                            Infolists\Components\TextEntry::make('toBranch.name')
                                ->label('Sucursal Destino')
                                ->badge()
                                ->color('success'),
                        ])->columns(2),
                    ]),

                Infolists\Components\Section::make('Seguimiento')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('requestedBy.name')
                                ->label('Solicitado Por')
                                ->badge()
                                ->color('info'),
                            Infolists\Components\TextEntry::make('sent_date')
                                ->label('Fecha de Envío')
                                ->date('d/m/Y H:i')
                                ->placeholder('No enviado aún'),
                            Infolists\Components\TextEntry::make('received_date')
                                ->label('Fecha de Recepción')
                                ->date('d/m/Y H:i')
                                ->placeholder('No recibido aún'),
                        ])->columns(3),
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('receivedBy.name')
                                ->label('Recibido Por')
                                ->badge()
                                ->color('success')
                                ->placeholder('Pendiente'),
                        ])->columns(1)
                            ->visible(fn ($record) => $record->received_by !== null),
                    ])
                    ->collapsed(),

                Infolists\Components\Section::make('Notas')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->notes)),
            ]);
    }
}
