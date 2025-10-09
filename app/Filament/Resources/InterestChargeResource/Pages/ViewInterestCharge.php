<?php

namespace App\Filament\Resources\InterestChargeResource\Pages;

use App\Filament\Resources\InterestChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInterestCharge extends ViewRecord
{
    protected static string $resource = InterestChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
