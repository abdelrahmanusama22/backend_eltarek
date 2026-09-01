<?php

namespace App\Jobs;

use App\Models\Brand;
use App\Models\Trim;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;
use OpenSpout\Reader\XLSX\Reader;

class ProcessCatalogImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes

    public function __construct(
        public string $filePath,
        public array $selectedColumns,
        public int $userId
    ) {}

    public function handle(): void
    {
        $reader = new Reader();
        $reader->open($this->filePath);
        
        $importedCount = 0;
        
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetName = $sheet->getName();
            
            // Skip non-catalog sheets
            if (in_array(strtolower($sheetName), ['settings', 'duplicate', 'edit', 'up - 5 %'])) {
                continue;
            }

            $header = [];
            $foundHeader = false;
            
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();
                
                if (!$foundHeader) {
                    $normalizedCells = array_map(fn($val) => trim(strtolower((string)$val)), $cells);
                    if (in_array('brand', $normalizedCells) && in_array('model', $normalizedCells)) {
                        $header = $normalizedCells;
                        $foundHeader = true;
                    }
                    continue;
                }
                
                $rowData = [];
                foreach ($header as $index => $colName) {
                    if ($colName && isset($cells[$index])) {
                        $rowData[$colName] = $cells[$index];
                    }
                }
                
                $brandName = trim((string)($rowData['brand'] ?? ''));
                $modelName = trim((string)($rowData['model'] ?? ''));
                $yearRaw = trim((string)($rowData['year'] ?? ''));
                
                $year = (int) preg_replace('/[^0-9]/', '', $yearRaw);
                if ($year < 1990 || $year > 2050) {
                    continue;
                }
                
                if (!$brandName || !$modelName || in_array(strtoupper($brandName), ['YES', 'NO', 'BRAND']) || in_array(strtoupper($modelName), ['YES', 'NO', 'MODEL'])) {
                    continue; 
                }

                // Trim Name could be in 'category' or 'trim' or derived from 'model sales code'
                $trimName = trim((string)($rowData['category'] ?? $rowData['trim'] ?? ''));
                if (!$trimName) {
                    $salesCode = trim((string)($rowData['model sales code'] ?? ''));
                    if ($salesCode) {
                        $trimName = trim(str_replace([$brandName, $modelName, (string)$year, '- retail', '- Retail', 'Retail'], '', $salesCode));
                        $trimName = trim($trimName, ' -');
                    }
                }
                
                if (!$trimName) {
                    $trimName = 'Standard';
                }

                // 1. Upsert Brand
                $brand = Brand::firstOrCreate(
                    ['name' => $brandName],
                    ['name_ar' => '', 'active' => true, 'sort' => 0]
                );

                // 2. Upsert Vehicle
                $vehicle = Vehicle::firstOrCreate(
                    ['brand_id' => $brand->id, 'model' => $modelName, 'year' => $year],
                    ['model_ar' => '', 'category' => 'Other', 'active' => true, 'sort' => 0, 'starting_price_egp' => 0]
                );

                // 3. Upsert Trim
                $trimData = [
                    'active' => true,
                    'name_ar' => '',
                    'highlights' => [],
                    'specs' => ['tech' => [], 'safety' => [], 'int' => [], 'ext' => []],
                    'metrics' => [
                        'hp' => ['display' => '', 'score' => 0],
                        'accel' => ['display' => '', 'score' => 0],
                        'speed' => ['display' => '', 'score' => 0]
                    ],
                    'gallery' => [],
                    'price_egp' => 0
                ];
                
                if (isset($rowData['car id'])) {
                    $trimData['legacy_car_id'] = $rowData['car id'];
                }

                // Pricing
                if (in_array('price_egp', $this->selectedColumns)) {
                    $officialPrice = $rowData['official price'] ?? $rowData['السعر الرسمى'] ?? 0;
                    $trimData['price_egp'] = (int) preg_replace('/[^0-9]/', '', (string)$officialPrice);
                }
                
                if (in_array('markup_percentage', $this->selectedColumns)) {
                    $officialPriceStr = $rowData['official price'] ?? $rowData['السعر الرسمى'] ?? 0;
                    $executivePriceStr = $rowData['price +5%'] ?? $rowData['السعر + 5 %'] ?? 0;
                    
                    $officialPrice = (int) preg_replace('/[^0-9]/', '', (string)$officialPriceStr);
                    $executivePrice = (int) preg_replace('/[^0-9]/', '', (string)$executivePriceStr);
                    
                    if ($officialPrice > 0 && $executivePrice > 0) {
                        $markup = (($executivePrice - $officialPrice) / $officialPrice) * 100;
                        $trimData['markup_percentage'] = round($markup, 2);
                    } else {
                        $trimData['markup_percentage'] = 5.00;
                    }
                }
                
                if (in_array('total_price', $this->selectedColumns)) {
                    $total = $rowData['total price'] ?? $rowData['اجمالى السعر'] ?? 0;
                    $trimData['total_price'] = (int) preg_replace('/[^0-9]/', '', (string)$total);
                }
                
                if (in_array('booking_deposit', $this->selectedColumns)) {
                    $deposit = $rowData['booking deposit'] ?? $rowData['مقدم الحجز'] ?? 0;
                    $trimData['booking_deposit'] = (int) preg_replace('/[^0-9]/', '', (string)$deposit);
                }
                
                if (in_array('is_on_hold', $this->selectedColumns)) {
                    $holdVal = strtolower(trim((string)($rowData['hold'] ?? 'no')));
                    $trimData['is_on_hold'] = ($holdVal === 'yes' || $holdVal === 'true' || $holdVal === '1');
                }
                
                if (in_array('colors', $this->selectedColumns)) {
                    $trimData['colors'] = trim((string)($rowData['colors'] ?? $rowData['الوان متاحة'] ?? ''));
                }
                
                if (in_array('financing_notes', $this->selectedColumns)) {
                    $trimData['financing_notes'] = trim((string)($rowData['financing notes'] ?? $rowData['معلومات اضافية'] ?? ''));
                }
                
                if (in_array('zero_interest_price', $this->selectedColumns)) {
                    $zero = $rowData['zero interest price'] ?? $rowData['عرض زيرو فائدة'] ?? 0;
                    $trimData['zero_interest_price'] = (int) preg_replace('/[^0-9]/', '', (string)$zero);
                }
                
                if (in_array('price_9pct', $this->selectedColumns)) {
                    $p9 = $rowData['install price 9%'] ?? $rowData['سعر السيارة قسط 9%'] ?? 0;
                    $trimData['price_9pct'] = (int) preg_replace('/[^0-9]/', '', (string)$p9);
                }

                Trim::updateOrCreate(
                    ['vehicle_id' => $vehicle->id, 'name' => $trimName],
                    $trimData
                );
                
                $importedCount++;
            }
        }
        
        $reader->close();
        
        $user = \App\Models\User::find($this->userId);
        if ($user) {
            Notification::make()
                ->title('Catalog Import Completed')
                ->body("Successfully imported/updated {$importedCount} trims.")
                ->success()
                ->sendToDatabase($user);
        }
    }
}
