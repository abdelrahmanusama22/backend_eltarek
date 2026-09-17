<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\HomeController;
use App\Models\AppSetting;
use App\Models\Brand;
use App\Models\Trim;
use App\Models\Vehicle;
use App\Support\CatalogEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeControllerCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_endpoint_caches_payload_and_serves_from_cache(): void
    {
        Cache::forget(HomeController::cacheKey());

        $brand = Brand::create([
            'name' => 'Mercedes-Benz',
            'name_ar' => 'مرسيدس بنز',
            'tier' => 'luxury',
            'active' => true,
        ]);

        $vehicle = Vehicle::create([
            'brand_id' => $brand->id,
            'model' => 'C200',
            'model_ar' => 'سي 200',
            'year' => 2025,
            'category' => 'Sedan',
            'starting_price_egp' => 3500000,
            'image_url' => 'vehicles/c200.jpg',
            'active' => true,
        ]);

        $trim = Trim::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'AMG Line',
            'name_ar' => 'إيه إم جي لاين',
            'price_egp' => 3800000,
            'highlights' => [],
            'specs' => [],
            'metrics' => [],
            'gallery' => [],
            'active' => true,
        ]);

        AppSetting::put('home_hero_vehicle_ids', [$vehicle->id]);
        AppSetting::put('smart_matches', [['trim_id' => $trim->id, 'match_percentage' => 95]]);
        AppSetting::put('budget_pick_trim_ids', [$trim->id]);

        // Ensure cache is clear before first request
        Cache::forget(HomeController::cacheKey());
        $this->assertFalse(Cache::has(HomeController::cacheKey()));

        // 1. First request should populate cache
        $firstResponse = $this->getJson('/api/v1/home');
        $firstResponse->assertOk()
            ->assertHeader('Cache-Control', 'max-age=120, public')
            ->assertJsonPath('data.heroes.0.id', $vehicle->id)
            ->assertJsonPath('data.brands.0.id', $brand->id);

        $this->assertTrue(Cache::has(HomeController::cacheKey()));
        $cachedData = Cache::get(HomeController::cacheKey());
        $this->assertIsArray($cachedData);
        $this->assertNotEmpty($cachedData['heroes']);

        // 2. Second request should serve directly from cache with 0 database queries
        DB::enableQueryLog();
        DB::flushQueryLog();

        $secondResponse = $this->getJson('/api/v1/home');
        $secondResponse->assertOk();

        $queries = DB::getQueryLog();
        // Since payload is cached, no queries should touch vehicles, brands, or trims
        $tableQueries = collect($queries)->filter(function ($q) {
            $sql = strtolower($q['query']);

            return str_contains($sql, 'vehicles') || str_contains($sql, 'brands') || str_contains($sql, 'trims');
        });
        $this->assertCount(0, $tableQueries, 'Cached home response should execute 0 queries on vehicles, brands, or trims');
    }

    public function test_app_setting_update_invalidates_home_cache(): void
    {
        Cache::put(HomeController::CACHE_KEY, ['dummy' => 'payload'], 3600);
        $this->assertTrue(Cache::has(HomeController::CACHE_KEY));

        AppSetting::put('some_setting', 'new_value');

        $this->assertFalse(Cache::has(HomeController::CACHE_KEY), 'AppSetting::put must invalidate home payload cache');
    }

    public function test_catalog_events_broadcast_invalidates_home_cache(): void
    {
        Cache::put(HomeController::CACHE_KEY, ['dummy' => 'payload'], 3600);
        $this->assertTrue(Cache::has(HomeController::CACHE_KEY));

        CatalogEvents::broadcast('Test update');

        $this->assertFalse(Cache::has(HomeController::CACHE_KEY), 'CatalogEvents::broadcast must invalidate home payload cache');
    }

    public function test_catalog_change_uses_a_new_home_cache_key_without_dashboard_save(): void
    {
        $oldKey = HomeController::cacheKey();
        Cache::put($oldKey, ['heroes' => []], 3600);

        CatalogEvents::broadcast('Vehicle changed');

        $this->assertNotSame($oldKey, HomeController::cacheKey());
        $this->assertFalse(Cache::has(HomeController::cacheKey()));
    }
}
