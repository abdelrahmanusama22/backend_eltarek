<?php

namespace App\Filament\Resources\Trims\Pages;

use App\Filament\Resources\Trims\TrimResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrim extends EditRecord
{
    protected static string $resource = TrimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
