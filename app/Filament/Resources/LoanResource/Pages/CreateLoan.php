<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Check credit limit enforcement
        if (isset($data['customer_id']) && isset($data['loan_amount'])) {
            $customer = Customer::find($data['customer_id']);

            if ($customer) {
                // Check if loan amount exceeds credit limit
                if ($data['loan_amount'] > $customer->credit_limit) {
                    // Send warning notification
                    Notification::make()
                        ->warning()
                        ->title('Límite de Crédito Excedido')
                        ->body("El monto del préstamo (Q" . number_format($data['loan_amount'], 2) . ") excede el límite de crédito del cliente (Q" . number_format($customer->credit_limit, 2) . "). Se requiere aprobación de gerente.")
                        ->persistent()
                        ->send();
                }

                // Check credit score for risk assessment
                if ($customer->credit_score && $customer->credit_score < 500) {
                    Notification::make()
                        ->danger()
                        ->title('Cliente de Alto Riesgo')
                        ->body("El cliente tiene un puntaje de crédito bajo ({$customer->credit_score} - {$customer->credit_rating}). Se recomienda revisión adicional.")
                        ->persistent()
                        ->send();
                }

                // Check for active overdue loans
                $overdueLoans = $customer->loans()->where('status', 'overdue')->count();
                if ($overdueLoans > 0) {
                    Notification::make()
                        ->warning()
                        ->title('Cliente con Préstamos Vencidos')
                        ->body("El cliente tiene {$overdueLoans} préstamo(s) vencido(s). Verificar historial de pagos antes de aprobar.")
                        ->persistent()
                        ->send();
                }
            }
        }

        return $data;
    }
}
