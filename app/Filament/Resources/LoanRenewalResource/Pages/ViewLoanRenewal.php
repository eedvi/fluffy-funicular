<?php

namespace App\Filament\Resources\LoanRenewalResource\Pages;

use App\Filament\Resources\LoanRenewalResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLoanRenewal extends ViewRecord
{
    protected static string $resource = LoanRenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
