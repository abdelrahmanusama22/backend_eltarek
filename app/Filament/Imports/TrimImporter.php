<?php

namespace App\Filament\Imports;

use App\Models\Trim;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class TrimImporter extends Importer
{
    protected static ?string $model = Trim::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->label('ID')
                ->numeric()
                ->rules(['integer']),
                
            // Non-database / Related fields (Ignored on Trim save)
            ImportColumn::make('vehicle_brand_name')
                ->label('Brand')
                ->fillRecordUsing(fn() => null),
            ImportColumn::make('vehicle_model')
                ->label('Model')
                ->fillRecordUsing(fn() => null),
            ImportColumn::make('year')
                ->label('Year')
                ->fillRecordUsing(fn() => null), // Handled in afterSave
            ImportColumn::make('legacy_car_id')
                ->label('Legacy Car ID')
                ->fillRecordUsing(fn() => null),
            ImportColumn::make('executive_price')
                ->label('Executive Price EGP')
                ->fillRecordUsing(fn() => null), // Computed attribute
            ImportColumn::make('category')
                ->label('Category')
                ->fillRecordUsing(fn() => null), // Handled in afterSave
            ImportColumn::make('vehicle_engine_summary')
                ->label('Engine Summary')
                ->fillRecordUsing(fn() => null),

            // Actual Database fields
            ImportColumn::make('name')
                ->label('Trim Name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('name_ar')
                ->label('Trim Name AR')
                ->castStateUsing(fn (?string $state): string => (string) $state),
            ImportColumn::make('price_egp')
                ->label('Official Price EGP')
                ->numeric()
                ->castStateUsing(fn (?string $state): int => (int) $state),
            ImportColumn::make('markup_percentage')
                ->label('Markup %')
                ->numeric()
                ->rules(['numeric', 'nullable']),
            ImportColumn::make('total_price')
                ->label('Total Price EGP')
                ->numeric()
                ->rules(['integer', 'nullable']),
            ImportColumn::make('booking_deposit')
                ->label('Booking Deposit')
                ->numeric()
                ->rules(['integer', 'nullable']),
            ImportColumn::make('zero_interest_price')
                ->label('Zero Interest Price')
                ->numeric()
                ->rules(['integer', 'nullable']),
            ImportColumn::make('price_9pct')
                ->label('Install Price 9%')
                ->numeric()
                ->rules(['integer', 'nullable']),
            ImportColumn::make('is_on_hold')
                ->label('Hold Status')
                ->boolean()
                ->castStateUsing(fn (?string $state): bool => filter_var($state, FILTER_VALIDATE_BOOLEAN)),
            ImportColumn::make('colors')
                ->label('Colors')
                ->castStateUsing(function (?string $state): ?array {
                    return blank($state) ? null : json_decode($state, true);
                }),
            ImportColumn::make('financing_notes')
                ->label('Financing Notes'),
            ImportColumn::make('is_most_popular')
                ->label('Most Popular')
                ->boolean()
                ->castStateUsing(fn (?string $state): bool => filter_var($state, FILTER_VALIDATE_BOOLEAN)),
            ImportColumn::make('active')
                ->label('Active')
                ->boolean()
                ->castStateUsing(fn (?string $state): bool => filter_var($state, FILTER_VALIDATE_BOOLEAN)),
            ImportColumn::make('subtitle')
                ->label('Subtitle')
                ->castStateUsing(fn (?string $state): string => (string) $state),
            ImportColumn::make('has_360_view')
                ->label('Has 360 View')
                ->boolean()
                ->castStateUsing(fn (?string $state): bool => filter_var($state, FILTER_VALIDATE_BOOLEAN)),
            ImportColumn::make('specs')
                ->label('Specifications')
                ->fillRecordUsing(function (Trim $record, ?string $state): void {
                    if (filled($state)) {
                        $record->specs = json_decode($state, true) ?? [];
                    }
                }),
            ImportColumn::make('highlights')
                ->label('Highlights')
                ->fillRecordUsing(function (Trim $record, ?string $state): void {
                    if (filled($state)) {
                        $record->highlights = json_decode($state, true) ?? [];
                    }
                }),
            ImportColumn::make('metrics')
                ->label('Metrics')
                ->fillRecordUsing(function (Trim $record, ?string $state): void {
                    if (filled($state)) {
                        $record->metrics = json_decode($state, true) ?? [];
                    }
                }),
        ];
    }

    public function resolveRecord(): ?Trim
    {
        $brandName = trim($this->data['vehicle_brand_name'] ?? '');
        $modelName = trim($this->data['vehicle_model'] ?? '');
        $year = $this->data['year'] ?? null;
        $trimName = trim($this->data['name'] ?? '');

        // Fallback: If ID is provided, look it up directly.
        if (!empty($this->data['id'])) {
            return Trim::find($this->data['id']) ?? new Trim();
        }

        // If basic relationships are missing, fallback to new Trim and let validation fail or save orphaned.
        if (empty($brandName) || empty($modelName) || empty($trimName)) {
            return new Trim();
        }

        // 1. Safely resolve or create the Brand
        $brand = \App\Models\Brand::firstOrCreate(
            ['name' => $brandName],
            ['active' => true]
        );

        // 2. Safely resolve or create the Vehicle
        $vehicle = \App\Models\Vehicle::firstOrCreate(
            [
                'brand_id' => $brand->id,
                'model'    => $modelName,
                'year'     => $year,
            ],
            [
                'category'           => $this->data['category'] ?? null,
                'engine_summary'     => $this->data['vehicle_engine_summary'] ?? null,
                'active'             => true,
                'starting_price_egp' => 0,
            ]
        );

        // 3. Resolve or create Trim
        return Trim::firstOrNew([
            'vehicle_id' => $vehicle->id,
            'name'       => $trimName,
        ]);
    }
    
    protected function afterSave(): void
    {
        $vehicle = $this->record->vehicle;
        
        if ($vehicle) {
            $vehicleUpdates = [];
            
            if (isset($this->data['category']) && filled($this->data['category'])) {
                $vehicleUpdates['category'] = $this->data['category'];
            }
            
            if (isset($this->data['year']) && filled($this->data['year'])) {
                $vehicleUpdates['year'] = $this->data['year'];
            }

            if (isset($this->data['vehicle_engine_summary']) && filled($this->data['vehicle_engine_summary'])) {
                $vehicleUpdates['engine_summary'] = $this->data['vehicle_engine_summary'];
            }
            
            if (!empty($vehicleUpdates)) {
                $vehicle->update($vehicleUpdates);
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your trim import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
