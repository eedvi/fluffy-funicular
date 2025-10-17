<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Services\CreditScoreService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function afterCreate(): void
    {
        // Calcular automáticamente el puntaje de crédito (FICO real: requiere historial)
        $creditScoreService = new CreditScoreService();
        $creditScoreService->updateCustomerCreditScore($this->record);

        // Obtener límite recomendado
        $recommendedLimit = $creditScoreService->getRecommendedCreditLimit($this->record);

        // Actualizar con el límite recomendado
        $this->record->update([
            'credit_limit' => $recommendedLimit,
        ]);

        // Notificación según si tiene o no score crediticio
        if ($this->record->credit_score === null) {
            // Cliente nuevo sin historial
            Notification::make()
                ->success()
                ->title('Cliente Creado Exitosamente')
                ->body("Sin historial crediticio. Límite conservador asignado: Q" . number_format($recommendedLimit, 2) . " (1x ingreso mensual de Q" . number_format($this->record->monthly_income, 2) . "). El puntaje se calculará después del primer préstamo completado.")
                ->send();
        } else {
            // Cliente con historial (inusual en creación, pero posible si se importan datos)
            Notification::make()
                ->success()
                ->title('Cliente Creado Exitosamente')
                ->body("Puntaje de crédito: {$this->record->credit_score} ({$this->record->credit_rating}). Límite de crédito asignado: Q" . number_format($recommendedLimit, 2) . " (basado en ingreso mensual de Q" . number_format($this->record->monthly_income, 2) . ")")
                ->send();
        }
    }
}
