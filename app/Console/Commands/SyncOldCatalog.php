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
            $url = env('CATALOG_SYNC_URL', 'https://test.dfskegypt.com/api/export-catalog');
            $response = Http::timeout(30)->acceptJson()->get($url);

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
                    $year = $item['year'] ?? null;
                    if ($year && in_array((string)$year, ['2020', '2021', '2022', '2023', '2024'])) {
                        return; // Skip vehicles from 2020-2024
                    }
                    $modelName = ($item['model'] ?? '') . ' ' . ($item['model_ar'] ?? '');
                    if (preg_match('/2020|2021|2022|2023|2024/', $modelName)) {
                        return; // Skip if year is mentioned in model name
                    }

                    $trims = $item['trims'] ?? [];
                    if (is_array($trims)) {
                        $trims = array_filter($trims, function ($trimData) {
                            $price = $trimData['price_egp'] ?? null;
                            if (empty($price) || floatval($price) < 200000) {
                                return false;
                            }
                            $trimName = $trimData['name'] ?? '';
                            if (preg_match('/2020|2021|2022|2023|2024/', $trimName)) {
                                return false;
                            }
                            return true;
                        });
                    }

                    if (empty($trims)) {
                        return; // Skip vehicle if no valid trims remain
                    }

                    $minPrice = collect($trims)->min('price_egp') ?? 0;
                    if ($minPrice < 200000) {
                        return;
                    }

                    // Sync Brand
                    $brandData = $item['brand'] ?? [];
                    $brandId = $brandData['id'] ?? $item['brand_id'] ?? null;
                    $brandName = $brandData['name'] ?? $item['brand_name'] ?? 'Unknown';

                    if (! $brandId) {
                        return; // Cannot sync without brand id
                    }

                    $brand = Brand::withTrashed()->updateOrCreate(
                        ['name' => $brandName],
                        [
                            'name_ar' => $brandData['name_ar'] ?? $brandName,
                        ]
                    );
                    if ($brand->trashed()) {
                        $brand->restore();
                    }

                    // Sync Vehicle
                    $vehicle = Vehicle::withTrashed()->updateOrCreate(
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
                    if ($vehicle->trashed()) {
                        $vehicle->restore();
                    }

                    // Sync Trims
                    foreach ($trims as $trimData) {
                        if (isset($trimData['price_egp']) && floatval($trimData['price_egp']) < 200000) {
                            continue;
                        }

                        Trim::withoutEvents(function () use ($vehicle, $trimData) {
                                $trim = Trim::withTrashed()
                                    ->where('vehicle_id', $vehicle->id)
                                    ->where('name', $trimData['name'] ?? null)
                                    ->first();

                                $attributes = [
                                    'name_ar' => $trimData['name_ar'] ?? $trimData['name'] ?? 'Unknown',
                                    'price_egp' => $trimData['price_egp'] ?? 0,
                                    'highlights' => $trimData['highlights'] ?? [],
                                    'specs' => $trimData['specs'] ?? [],
                                    'metrics' => $trimData['metrics'] ?? [],
                                    'gallery' => $trimData['gallery'] ?? [],
                                ];

                                if (!$trim) {
                                    $attributes['vehicle_id'] = $vehicle->id;
                                    $attributes['name'] = $trimData['name'] ?? null;
                                    $attributes['active'] = $trimData['active'] ?? true;
                                    $trim = Trim::create($attributes);
                                } else {
                                    $trim->update($attributes);
                                }

                                if ($trim->trashed()) {
                                    $trim->restore();
                                }
                            });
                        }
                });
            });


            $this->newLine();
            $this->info('Catalog sync completed successfully.');

            \Illuminate\Support\Facades\Cache::flush();
            \App\Support\CatalogEvents::broadcast();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('An error occurred during sync: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}

