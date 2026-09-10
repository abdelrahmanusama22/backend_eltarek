<?php

namespace App\Filament\Exports;

use App\Models\Trim;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;

class TrimExporter extends Exporter
{
    protected static ?string $model = Trim::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('vehicle.brand.name')->label('Brand'),
            ExportColumn::make('vehicle.model')->label('Model'),
            ExportColumn::make('vehicle.year')->label('Year'),
            ExportColumn::make('legacy_car_id')->label('Legacy Car ID'),
            ExportColumn::make('name')->label('Trim Name'),
            ExportColumn::make('name_ar')->label('Trim Name AR'),
            ExportColumn::make('price_egp')->label('Official Price EGP'),
            ExportColumn::make('executive_price')->label('Executive Price EGP')
                ->state(fn (Trim $record) => $record->executive_price),
            ExportColumn::make('markup_percentage')->label('Markup %'),
            ExportColumn::make('total_price')->label('Total Price EGP'),
            ExportColumn::make('booking_deposit')->label('Booking Deposit'),
            ExportColumn::make('zero_interest_price')->label('Zero Interest Price'),
            ExportColumn::make('price_9pct')->label('Install Price 9%'),
            ExportColumn::make('is_on_hold')->label('Hold Status'),
            ExportColumn::make('colors')->label('Colors'),
            ExportColumn::make('financing_notes')->label('Financing Notes'),
            ExportColumn::make('is_most_popular')->label('Most Popular'),
            ExportColumn::make('active')->label('Active'),
            ExportColumn::make('vehicle.category')->label('Category'),
            ExportColumn::make('vehicle.engine_summary')->label('Engine Summary'),
            ExportColumn::make('subtitle')->label('Subtitle'),
            ExportColumn::make('has_360_view')->label('Has 360 View'),
            ExportColumn::make('specs')
                ->label('Specifications')
                ->state(fn (Trim $record) => $record->specs ? json_encode($record->specs, JSON_UNESCAPED_UNICODE) : null),
            ExportColumn::make('highlights')
                ->label('Highlights')
                ->state(fn (Trim $record) => $record->highlights ? json_encode($record->highlights, JSON_UNESCAPED_UNICODE) : null),
            ExportColumn::make('metrics')
                ->label('Metrics')
                ->state(fn (Trim $record) => $record->metrics ? json_encode($record->metrics, JSON_UNESCAPED_UNICODE) : null),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your trim export has completed and ' . Str::of('row')->counted($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Str::of('row')->counted($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
