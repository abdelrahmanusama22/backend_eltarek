<?php

namespace App\Filament\Exports;

use App\Models\Vehicle;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;

class VehicleExporter extends Exporter
{
    protected static ?string $model = Vehicle::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('brand.name')->label('Brand'),
            ExportColumn::make('model')->label('Model'),
            ExportColumn::make('model_ar')->label('Model AR'),
            ExportColumn::make('year')->label('Year'),
            ExportColumn::make('category')->label('Category'),
            ExportColumn::make('starting_price_egp')->label('Starting Price EGP'),
            ExportColumn::make('image_url')->label('Image URL'),
            ExportColumn::make('engine_summary')->label('Engine Summary'),
            ExportColumn::make('monthly_from_egp')->label('Monthly From EGP'),
            ExportColumn::make('badge')->label('Badge'),
            ExportColumn::make('sort')->label('Sort Order'),
            ExportColumn::make('active')->label('Active'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your vehicle export has completed and '.Str::of('row')->counted($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Str::of('row')->counted($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
