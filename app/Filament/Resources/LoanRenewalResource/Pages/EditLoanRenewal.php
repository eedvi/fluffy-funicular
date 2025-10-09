<?php

namespace App\Filament\Resources\LoanRenewalResource\Pages;

use App\Filament\Resources\LoanRenewalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoanRenewal extends EditRecord
{
    protected static string $resource = LoanRenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
