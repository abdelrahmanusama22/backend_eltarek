<?php

namespace App\Filament\Resources\Trims\Pages;

use App\Filament\Actions\ExcelImportAction;
use App\Filament\Imports\TrimImporter;
use App\Filament\Resources\Trims\TrimResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrims extends ListRecords
{
    protected static string $resource = TrimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make('import')
                ->importer(TrimImporter::class),
            CreateAction::make(),
        ];
    }
}
