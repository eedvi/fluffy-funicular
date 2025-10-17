<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Services\CreditScoreService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('recalcular_puntaje')
                ->label('Recalcular Puntaje de Crédito')
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Recalcular Puntaje de Crédito')
                ->modalDescription('¿Desea recalcular el puntaje de crédito basado en el historial actual? Requiere al menos 1 préstamo completado.')
                ->modalSubmitActionLabel('Sí, Recalcular')
                ->action(function () {
                    $creditScoreService = new CreditScoreService();
                    $creditScoreService->updateCustomerCreditScore($this->record);

                    $recommendedLimit = $creditScoreService->getRecommendedCreditLimit($this->record);

                    if ($this->record->credit_score === null) {
                        Notification::make()
                            ->warning()
                            ->title('Sin Historial Suficiente')
                            ->body('El cliente necesita al menos 1 préstamo completado (pagado o confiscado) para calcular un puntaje crediticio. Límite conservador: Q' . number_format($recommendedLimit, 2))
                            ->send();
                    } else {
                        Notification::make()
                            ->success()
                            ->title('Puntaje de Crédito Actualizado')
                            ->body("Nuevo puntaje: {$this->record->credit_score} ({$this->record->credit_rating}). Límite recomendado: Q" . number_format($recommendedLimit, 2) . " (basado en ingreso mensual de Q" . number_format($this->record->monthly_income, 2) . ")")
                            ->send();
                    }

                    // Refrescar la página para mostrar los nuevos valores
                    $this->refreshFormData([
                        'credit_score',
                        'credit_rating',
                        'credit_score_updated_at',
                    ]);
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
