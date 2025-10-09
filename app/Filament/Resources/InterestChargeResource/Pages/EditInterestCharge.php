<?php

namespace App\Filament\Resources\InterestChargeResource\Pages;

use App\Filament\Resources\InterestChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInterestCharge extends EditRecord
{
    protected static string $resource = InterestChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
