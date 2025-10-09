<?php

namespace App\Filament\Resources\InterestChargeResource\Pages;

use App\Filament\Resources\InterestChargeResource;
use App\Models\Loan;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInterestCharge extends CreateRecord
{
    protected static string $resource = InterestChargeResource::class;

    protected function afterCreate(): void
    {
        $interestCharge = $this->record;

        // If the charge is marked as applied, update the loan's current balance
        if ($interestCharge->is_applied) {
            $loan = Loan::find($interestCharge->loan_id);

            if ($loan) {
                $loan->update([
                    'current_balance' => $interestCharge->balance_after,
                ]);

                Notification::make()
                    ->success()
                    ->title('Préstamo actualizado')
                    ->body("El balance del préstamo {$loan->loan_number} se actualizó a Q" . number_format($interestCharge->balance_after, 2))
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
