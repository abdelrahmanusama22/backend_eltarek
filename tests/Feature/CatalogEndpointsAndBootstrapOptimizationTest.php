<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\City;
use App\Models\Trim;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogEndpointsAndBootstrapOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function seedCatalog(): array
    {
        $city = City::create([
            'name' => 'Cairo',
            'name_ar' => 'القاهرة',
            'sort' => 1,
        ]);

        $brand = Brand::create([
            'name' => 'Toyota',
            'name_ar' => 'تويوتا',
            'tagline' => 'Quality',
            'tagline_ar' => 'جودة',
            'sort' => 1,
            'active' => true,
        ]);

        $vehicle = Vehicle::create([
            'brand_id' => $brand->id,
            'model' => 'Corolla',
            'model_ar' => 'كورولا',
            'year' => 2026,
            'category' => 'Sedan',
            'starting_price_egp' => 1200000,
            'active' => true,
        ]);

        $trim1 = Trim::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Active',
            'name_ar' => 'اكتيف',
            'price_egp' => 1250000,
            'markup_percentage' => 4.0,
            'highlights' => [['icon' => 'sunroof', 'label' => 'Sunroof', 'label_ar' => 'فتحة سقف']],
            'specs' => ['tech' => ['engine' => '1.6L']],
            'metrics' => ['hp' => ['120 hp', 120]],
            'gallery' => ['trims/corolla_1.jpg'],
            'active' => true,
            'in_test_drive_fleet' => true,
        ]);

        $trim2 = Trim::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Comfort',
            'name_ar' => 'كومفورت',
            'price_egp' => 1400000,
            'markup_percentage' => 5.0,
            'highlights' => [],
            'specs' => [],
            'metrics' => [],
            'gallery' => [],
            'active' => true,
            'in_test_drive_fleet' => false,
        ]);

        $branch = Branch::create([
            'name' => 'Main Showroom',
            'name_ar' => 'المعرض الرئيسي',
            'city_id' => $city->id,
            'address' => 'Nasr City',
            'address_ar' => 'مدينة نصر',
            'phone' => '19022',
            'hours' => '9 AM - 10 PM',
            'hours_ar' => '9 ص - 10 م',
            'lat' => 30.05,
            'lng' => 31.35,
            'active' => true,
        ]);

        return compact('city', 'brand', 'vehicle', 'trim1', 'trim2', 'branch');
    }

    public function test_granular_catalog_vehicles_does_not_duplicate_trims(): void
    {
        $this->seedCatalog();

        $response = $this->getJson('/api/v1/catalog/vehicles?limit=50');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => ['catalog_version', 'next_cursor', 'has_more'],
        ]);

        $vehicles = $response->json('data');
        $this->assertNotEmpty($vehicles);
        $this->assertEmpty($vehicles[0]['trims'], 'Vehicles in /catalog/vehicles should not embed full duplicate trims.');
        $this->assertGreaterThan(0, $vehicles[0]['starting_price_egp']);
    }

    public function test_granular_catalog_trims_and_brands_endpoints_return_data(): void
    {
        $this->seedCatalog();

        $brandsResponse = $this->getJson('/api/v1/catalog/brands')->assertOk();
        $this->assertCount(1, $brandsResponse->json('data'));
        $this->assertSame('Toyota', $brandsResponse->json('data.0.name'));

        $trimsResponse = $this->getJson('/api/v1/catalog/trims?limit=50')->assertOk();
        $this->assertCount(2, $trimsResponse->json('data'));
        $this->assertSame('Active', $trimsResponse->json('data.0.name'));
    }
}
