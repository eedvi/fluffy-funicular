<?php

namespace App\Filament\Resources\LoanRenewalResource\Pages;

use App\Filament\Resources\LoanRenewalResource;
use App\Models\Loan;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLoanRenewal extends CreateRecord
{
    protected static string $resource = LoanRenewalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Renovación creada exitosamente';
    }

    protected function afterCreate(): void
    {
        // Update the loan's due date with the new due date from the renewal
        $renewal = $this->record;
        $loan = Loan::find($renewal->loan_id);

        if ($loan) {
            $loan->update([
                'due_date' => $renewal->new_due_date,
            ]);

            // If loan was overdue, set it back to active
            if ($loan->status === 'overdue') {
                $loan->update(['status' => 'active']);
            }

            // Send additional notification
            Notification::make()
                ->success()
                ->title('Préstamo actualizado')
                ->body("El vencimiento del préstamo {$loan->loan_number} se actualizó al {$renewal->new_due_date->format('d/m/Y')}")
                ->send();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure processed_by is set to current user
        $data['processed_by'] = $data['processed_by'] ?? auth()->id();

        return $data;
    }
}
