<?php

namespace App\Filament\Resources\GarageCars\Pages;

use App\Filament\Resources\GarageCars\GarageCarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGarageCars extends ListRecords
{
    protected static string $resource = GarageCarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
