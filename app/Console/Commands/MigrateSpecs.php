<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trim;

class MigrateSpecs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-specs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing Trim specs to the new structured format.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $trims = Trim::all();
        $count = 0;
        
        foreach ($trims as $trim) {
            $specs = $trim->specs;
            if (!$specs || !is_array($specs)) continue;
            
            $newSpecs = [
                'tech' => [
                    'engine' => null,
                    'hp' => null,
                    'transmission' => null,
                    'custom_tech' => []
                ],
                'safety' => [
                    'airbags' => null,
                    'abs_ebd' => null,
                    'custom_safety' => []
                ],
                'interior' => [
                    'seats_material' => null,
                    'screen_size' => null,
                    'custom_interior' => []
                ],
                'exterior' => [
                    'wheels_size' => null,
                    'sunroof' => null,
                    'custom_exterior' => []
                ]
            ];
            
            // Map Tech
            if (isset($specs['tech']) && is_array($specs['tech'])) {
                // If it's already migrated (has custom_tech), skip to avoid double mapping, or handle gracefully
                if (isset($specs['tech']['custom_tech'])) {
                    $newSpecs['tech'] = $specs['tech'];
                } else {
                    foreach ($specs['tech'] as $item) {
                        $label = strtolower($item['label'] ?? '');
                        if (str_contains($label, 'engine') || str_contains($label, 'محرك') || str_contains($label, 'سعة')) {
                            $newSpecs['tech']['engine'] = $this->cloneValue($newSpecs['tech']['engine'], $item['value']);
                        } elseif (str_contains($label, 'hp') || str_contains($label, 'حصان') || str_contains($label, 'horse') || str_contains($label, 'قوة')) {
                            $newSpecs['tech']['hp'] = $this->cloneValue($newSpecs['tech']['hp'], $item['value']);
                        } elseif (str_contains($label, 'transmission') || str_contains($label, 'ناقل') || str_contains($label, 'فتيس') || str_contains($label, 'gear')) {
                            $newSpecs['tech']['transmission'] = $this->cloneValue($newSpecs['tech']['transmission'], $item['value']);
                        } else {
                            $newSpecs['tech']['custom_tech'][] = $item;
                        }
                    }
                }
            }
            
            // Map Safety
            if (isset($specs['safety']) && is_array($specs['safety'])) {
                if (isset($specs['safety']['custom_safety'])) {
                    $newSpecs['safety'] = $specs['safety'];
                } else {
                    foreach ($specs['safety'] as $item) {
                        $label = strtolower($item['label'] ?? '');
                        if (str_contains($label, 'airbag') || str_contains($label, 'وسائد') || str_contains($label, 'وسادة')) {
                            $newSpecs['safety']['airbags'] = $this->cloneValue($newSpecs['safety']['airbags'], $item['value']);
                        } elseif (str_contains($label, 'abs') || str_contains($label, 'ebd') || str_contains($label, 'فرامل')) {
                            $newSpecs['safety']['abs_ebd'] = $this->cloneValue($newSpecs['safety']['abs_ebd'], $item['value']);
                        } else {
                            $newSpecs['safety']['custom_safety'][] = $item;
                        }
                    }
                }
            }
            
            // Map Interior (old was 'int')
            $oldInt = $specs['int'] ?? ($specs['interior'] ?? []);
            if (is_array($oldInt)) {
                if (isset($oldInt['custom_interior'])) {
                    $newSpecs['interior'] = $oldInt;
                } else {
                    foreach ($oldInt as $item) {
                        $label = strtolower($item['label'] ?? '');
                        if (str_contains($label, 'seat') || str_contains($label, 'مقاعد') || str_contains($label, 'فرش')) {
                            $newSpecs['interior']['seats_material'] = $this->cloneValue($newSpecs['interior']['seats_material'], $item['value']);
                        } elseif (str_contains($label, 'screen') || str_contains($label, 'شاشة')) {
                            $newSpecs['interior']['screen_size'] = $this->cloneValue($newSpecs['interior']['screen_size'], $item['value']);
                        } else {
                            $newSpecs['interior']['custom_interior'][] = $item;
                        }
                    }
                }
            }
            
            // Map Exterior (old was 'ext')
            $oldExt = $specs['ext'] ?? ($specs['exterior'] ?? []);
            if (is_array($oldExt)) {
                if (isset($oldExt['custom_exterior'])) {
                    $newSpecs['exterior'] = $oldExt;
                } else {
                    foreach ($oldExt as $item) {
                        $label = strtolower($item['label'] ?? '');
                        if (str_contains($label, 'wheel') || str_contains($label, 'جنوط') || str_contains($label, 'جنط') || str_contains($label, 'عجل')) {
                            $newSpecs['exterior']['wheels_size'] = $this->cloneValue($newSpecs['exterior']['wheels_size'], $item['value']);
                        } elseif (str_contains($label, 'sunroof') || str_contains($label, 'سقف')) {
                            $newSpecs['exterior']['sunroof'] = $this->cloneValue($newSpecs['exterior']['sunroof'], $item['value']);
                        } else {
                            $newSpecs['exterior']['custom_exterior'][] = $item;
                        }
                    }
                }
            }
            
            $trim->specs = $newSpecs;
            $trim->saveQuietly();
            $count++;
        }
        
        $this->info("Successfully migrated specs for {$count} trims.");
    }
    
    // Helper to concatenate if multiple items match
    private function cloneValue($existing, $new) {
        return $existing ? $existing . ' + ' . $new : $new;
    }
}
