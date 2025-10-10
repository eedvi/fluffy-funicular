<?php

namespace App\Filament\Resources\ItemTransferResource\Pages;

use App\Filament\Resources\ItemTransferResource;
use App\Models\ItemTransfer;
use Filament\Actions;
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
}
