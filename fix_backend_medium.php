<?php
// 1. Rate Limiting
$api_path = "routes/api.php";
$api_content = file_get_contents($api_path);
$api_content = str_replace(
    "Route::post('complete-profile', [AuthController::class, 'completeProfile']);",
    "Route::post('complete-profile', [AuthController::class, 'completeProfile'])->middleware('throttle:10,1');",
    $api_content
);
file_put_contents($api_path, $api_content);

// 2. Caching in BootstrapController
$boot_path = "app/Http/Controllers/Api/BootstrapController.php";
$boot_content = file_get_contents($boot_path);
if (strpos($boot_content, "use Illuminate\Support\Facades\Cache;") === false) {
    $boot_content = str_replace(
        "use Illuminate\Http\Request;",
        "use Illuminate\Http\Request;\nuse Illuminate\Support\Facades\Cache;",
        $boot_content
    );
}

$old_block = <<<'EOD'
        $fleetTrimIds = Trim::where('active', true)
            ->where('in_test_drive_fleet', true)
            ->orderBy('fleet_sort')
            ->pluck('id');

        return $this->ok([
            'settings' => [
                'compare_max' => AppSetting::get('compare_max', 3),
                'support_phone' => AppSetting::get('support_phone', '19022'),
                'finance' => AppSetting::get('finance'),
                'smart_matches' => AppSetting::get('smart_matches', []),
                'budget_pick_trim_ids' => AppSetting::get('budget_pick_trim_ids', []),
                'budget_section' => AppSetting::get('budget_section'),
                'financing_banner' => AppSetting::get('financing_banner'),
                'fleet_trim_ids' => $fleetTrimIds,
            ],
            'cities' => City::orderBy('sort')->get()->map->toApi(),
            'brands' => Brand::where('active', true)->orderBy('sort')->get()->map->toApi(),
            'vehicles' => Vehicle::where('active', true)->orderBy('sort')->get()->map->toApi(),
            'trims' => Trim::where('active', true)->get()->map->toApi(),
            'branches' => Branch::where('active', true)->get()->map->toApi(),
            'rewards' => Reward::where('active', true)->get()
                ->map(fn (Reward $r) => $r->toApi($user)),
            'slots' => SlotService::upcoming(),
            'garage' => $user?->garageCars()->get()->map->toApi(),
        ]);
EOD;

$new_block = <<<'EOD'
        $cacheKey = 'bootstrap_payload';
        $payload = Cache::remember($cacheKey, 3600, function() {
            $fleetTrimIds = Trim::where('active', true)
                ->where('in_test_drive_fleet', true)
                ->orderBy('fleet_sort')
                ->pluck('id');

            return [
                'settings' => [
                    'compare_max' => AppSetting::get('compare_max', 3),
                    'support_phone' => AppSetting::get('support_phone', '19022'),
                    'finance' => AppSetting::get('finance'),
                    'smart_matches' => AppSetting::get('smart_matches', []),
                    'budget_pick_trim_ids' => AppSetting::get('budget_pick_trim_ids', []),
                    'budget_section' => AppSetting::get('budget_section'),
                    'financing_banner' => AppSetting::get('financing_banner'),
                    'fleet_trim_ids' => $fleetTrimIds,
                ],
                'cities' => City::orderBy('sort')->get()->map->toApi(),
                'brands' => Brand::where('active', true)->orderBy('sort')->get()->map->toApi(),
                'vehicles' => Vehicle::where('active', true)->orderBy('sort')->get()->map->toApi(),
                'trims' => Trim::where('active', true)->get()->map->toApi(),
                'branches' => Branch::where('active', true)->get()->map->toApi(),
            ];
        });

        // Dynamic parts (user-specific or highly volatile)
        $payload['rewards'] = Reward::where('active', true)->get()
            ->map(fn (Reward $r) => $r->toApi($user));
        $payload['slots'] = SlotService::upcoming();
        $payload['garage'] = $user?->garageCars()->get()->map->toApi();

        return $this->ok($payload);
EOD;

$boot_content = str_replace($old_block, $new_block, $boot_content);
file_put_contents($boot_path, $boot_content);

echo "Backend medium bugs fixed!\n";
?>
