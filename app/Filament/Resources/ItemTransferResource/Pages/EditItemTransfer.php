<?php

namespace App\Filament\Resources\ItemTransferResource\Pages;

use App\Filament\Resources\ItemTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditItemTransfer extends EditRecord
{
    protected static string $resource = ItemTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
