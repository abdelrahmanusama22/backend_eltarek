<?php

namespace App\Filament\Resources\GarageCars\Pages;

use App\Filament\Resources\GarageCars\GarageCarResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGarageCar extends EditRecord
{
    protected static string $resource = GarageCarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
