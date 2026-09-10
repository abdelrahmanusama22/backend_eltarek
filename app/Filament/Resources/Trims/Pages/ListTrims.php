<?php

namespace App\Filament\Resources\Trims\Pages;

use App\Filament\Resources\Trims\TrimResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrims extends ListRecords
{
    protected static string $resource = TrimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\ExcelImportAction::make('import')
                ->importer(\App\Filament\Imports\TrimImporter::class),
            CreateAction::make(),
        ];
    }
}
