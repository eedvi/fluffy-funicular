<?php

namespace App\Filament\Resources\LoanRenewalResource\Pages;

use App\Filament\Resources\LoanRenewalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoanRenewals extends ListRecords
{
    protected static string $resource = LoanRenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
