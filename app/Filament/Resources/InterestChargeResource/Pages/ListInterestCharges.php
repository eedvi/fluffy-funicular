<?php

namespace App\Filament\Resources\InterestChargeResource\Pages;

use App\Filament\Resources\InterestChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInterestCharges extends ListRecords
{
    protected static string $resource = InterestChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
