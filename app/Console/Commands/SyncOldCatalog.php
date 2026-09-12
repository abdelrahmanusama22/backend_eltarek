<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Trim;
use App\Models\Vehicle;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

#[Signature('catalog:sync')]
#[Description('Sync catalog data from legacy system')]
class SyncOldCatalog extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting catalog sync...');

        try {
            $response = Http::timeout(30)->get('http://192.168.202.62:8087/api/export-catalog');

            if (! $response->successful()) {
                $this->error('Failed to fetch data. HTTP Status: '.$response->status());

                return self::FAILURE;
            }

            $data = $response->json('data');

            if (! is_array($data) || empty($data)) {
                $this->warn('No data found to sync.');

                return self::SUCCESS;
            }

            $this->withProgressBar($data, function ($item) {
                DB::transaction(function () use ($item) {
                    // Sync Brand
                    $brandData = $item['brand'] ?? [];
                    $brandId = $brandData['id'] ?? $item['brand_id'] ?? null;
                    $brandName = $brandData['name'] ?? $item['brand_name'] ?? 'Unknown';

                    if (! $brandId) {
                        return; // Cannot sync without brand id
                    }

                    $brand = Brand::updateOrCreate(
                        ['id' => $brandId],
                        [
                            'name' => $brandName,
                            'name_ar' => $brandData['name_ar'] ?? $brandName,
                        ]
                    );

                    $trims = $item['trims'] ?? [];
                    $minPrice = 0;
                    if (is_array($trims) && count($trims) > 0) {
                        $minPrice = collect($trims)->min('price_egp') ?? 0;
                    }

                    // Sync Vehicle
                    $vehicle = Vehicle::updateOrCreate(
                        [
                            'brand_id' => $brand->id,
                            'model' => $item['model'] ?? null,
                            'year' => $item['year'] ?? null,
                        ],
                        [
                            'model_ar' => $item['model_ar'] ?? $item['model'] ?? 'Unknown',
                            'category' => $item['category'] ?? 'Uncategorized',
                            'starting_price_egp' => $minPrice,
                        ]
                    );

                    // Sync Trims
                    if (is_array($trims)) {
                        foreach ($trims as $trimData) {
                            Trim::withoutEvents(function () use ($vehicle, $trimData) {
                                Trim::updateOrCreate(
                                    [
                                        'vehicle_id' => $vehicle->id,
                                        'name' => $trimData['name'] ?? null,
                                    ],
                                    [
                                        'name_ar' => $trimData['name_ar'] ?? $trimData['name'] ?? 'Unknown',
                                        'price_egp' => $trimData['price_egp'] ?? 0,
                                        'active' => $trimData['active'] ?? true,
                                        'highlights' => $trimData['highlights'] ?? [],
                                        'specs' => $trimData['specs'] ?? [],
                                        'metrics' => $trimData['metrics'] ?? [],
                                        'gallery' => $trimData['gallery'] ?? [],
                                    ]
                                );
                            });
                        }
                    }
                });
            });

            $this->newLine();
            $this->info('Catalog sync completed successfully.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('An error occurred during sync: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
