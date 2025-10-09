<?php

namespace App\Filament\Resources\LoyaltyProgramResource\Pages;

use App\Filament\Resources\LoyaltyProgramResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLoyaltyProgram extends CreateRecord
{
    protected static string $resource = LoyaltyProgramResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
