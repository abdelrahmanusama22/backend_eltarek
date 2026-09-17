<?php

namespace App\Filament\Exports;

use App\Models\Brand;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;

class BrandExporter extends Exporter
{
    protected static ?string $model = Brand::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Brand Name'),
            ExportColumn::make('name_ar')
                ->label('الاسم بالعربي'),
            ExportColumn::make('tagline')
                ->label('Tagline'),
            ExportColumn::make('tagline_ar')
                ->label('Tagline AR'),
            ExportColumn::make('monogram')
                ->label('Monogram'),
            ExportColumn::make('tier')
                ->label('Tier (premium/standard)'),
            ExportColumn::make('logo_url')
                ->label('Logo URL'),
            ExportColumn::make('sort')
                ->label('Sort Order'),
            ExportColumn::make('active')
                ->label('Active'),
            ExportColumn::make('vehicles_count')
                ->label('Vehicles Count')
                ->state(fn (Brand $record) => $record->vehicles()->count()),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your brand export has completed and '.Str::of('row')->counted($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Str::of('row')->counted($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
