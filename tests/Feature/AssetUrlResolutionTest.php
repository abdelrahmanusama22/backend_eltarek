<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\City;
use App\Models\GarageCar;
use App\Models\Trim;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetUrlResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_returns_standard_relative_media_path_and_preserves_external(): void
    {
        $city = City::create(['name' => 'Cairo', 'name_ar' => 'القاهرة', 'sort' => 1]);

        $localBranch = Branch::create([
            'city_id' => $city->id,
            'name' => 'Nasr City Showroom',
            'name_ar' => 'معرض مدينة نصر',
            'address' => 'Nasr City, Cairo',
            'address_ar' => 'مدينة نصر، القاهرة',
            'phone' => '19000',
            'hours' => '9 AM - 10 PM',
            'hours_ar' => '9 ص - 10 م',
            'lat' => 30.0500,
            'lng' => 31.3300,
            'image' => 'branches/nasr_city.jpg',
            'active' => true,
        ]);

        $externalBranch = Branch::create([
            'city_id' => $city->id,
            'name' => 'Alex Branch',
            'name_ar' => 'فرع الإسكندرية',
            'address' => 'Corniche, Alex',
            'address_ar' => 'كورنيش الإسكندرية',
            'phone' => '19000',
            'hours' => '9 AM - 10 PM',
            'hours_ar' => '9 ص - 10 م',
            'lat' => 31.2000,
            'lng' => 29.9167,
            'image' => 'https://example.com/cdn/alex.jpg',
            'active' => true,
        ]);

        $localApi = $localBranch->toApi();
        $this->assertSame('/media/branches/nasr_city.jpg', $localApi['image']);
        $this->assertSame('/media/branches/nasr_city.jpg', $localApi['image_url']);
        $this->assertStringNotContainsString('127.0.0.1', $localApi['image']);
        $this->assertStringNotContainsString('localhost', $localApi['image']);

        $externalApi = $externalBranch->toApi();
        $this->assertSame('https://example.com/cdn/alex.jpg', $externalApi['image']);
    }

    public function test_vehicle_returns_standard_relative_media_path_and_preserves_external(): void
    {
        $brand = Brand::create([
            'name' => 'Toyota',
            'name_ar' => 'تويوتا',
            'tier' => 'standard',
            'active' => true,
        ]);

        $localVehicle = Vehicle::create([
            'brand_id' => $brand->id,
            'model' => 'Corolla',
            'model_ar' => 'كورولا',
            'year' => 2025,
            'category' => 'Sedan',
            'starting_price_egp' => 1200000,
            'image_url' => 'vehicles/corolla.png',
            'active' => true,
        ]);

        $externalVehicle = Vehicle::create([
            'brand_id' => $brand->id,
            'model' => 'Camry',
            'model_ar' => 'كامري',
            'year' => 2025,
            'category' => 'Sedan',
            'starting_price_egp' => 2500000,
            'image_url' => 'https://images.unsplash.com/photo-corolla',
            'active' => true,
        ]);

        $localApi = $localVehicle->toApi();
        $this->assertSame('/media/vehicles/corolla.png', $localApi['image_url']);
        $this->assertStringNotContainsString('127.0.0.1', $localApi['image_url']);

        $externalApi = $externalVehicle->toApi();
        $this->assertSame('https://images.unsplash.com/photo-corolla', $externalApi['image_url']);
    }

    public function test_brand_returns_standard_relative_media_path(): void
    {
        $brand = Brand::create([
            'name' => 'BMW',
            'name_ar' => 'بي إم دبليو',
            'logo_url' => 'brands/bmw.svg',
            'tier' => 'premium',
            'active' => true,
        ]);

        $api = $brand->toApi();
        $this->assertSame('/media/brands/bmw.svg', $api['logo_url']);
        $this->assertStringNotContainsString('127.0.0.1', $api['logo_url']);
    }

    public function test_trim_returns_standard_relative_media_paths_for_gallery(): void
    {
        $brand = Brand::create(['name' => 'Kia', 'name_ar' => 'كيا', 'tier' => 'standard', 'active' => true]);
        $vehicle = Vehicle::create([
            'brand_id' => $brand->id,
            'model' => 'Sportage',
            'model_ar' => 'سبورتاج',
            'year' => 2025,
            'category' => 'SUV',
            'starting_price_egp' => 1500000,
            'active' => true,
        ]);

        $trim = Trim::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'GT-Line',
            'name_ar' => 'جي تي لاين',
            'price_egp' => 1800000,
            'highlights' => [],
            'specs' => [],
            'metrics' => [],
            'gallery' => [
                'trims/photo1.jpg',
                '/storage/trims/photo2.jpg',
                'https://cdn.eltarek.com/gallery3.jpg',
            ],
            'active' => true,
        ]);

        $api = $trim->toApi();
        $this->assertCount(3, $api['gallery']);
        $this->assertSame('/media/trims/photo1.jpg', $api['gallery'][0]);
        $this->assertSame('/media/trims/photo2.jpg', $api['gallery'][1]);
        $this->assertSame('https://cdn.eltarek.com/gallery3.jpg', $api['gallery'][2]);
        $this->assertStringNotContainsString('127.0.0.1', $api['gallery'][0]);
        $this->assertStringNotContainsString('127.0.0.1', $api['gallery'][1]);
    }

    public function test_garage_car_returns_standard_relative_media_path(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'Audi', 'name_ar' => 'أودي', 'tier' => 'premium', 'active' => true]);
        $vehicle = Vehicle::create([
            'brand_id' => $brand->id,
            'model' => 'A6',
            'model_ar' => 'إيه 6',
            'year' => 2024,
            'category' => 'Sedan',
            'starting_price_egp' => 2800000,
            'image_url' => 'vehicles/a6.jpg',
            'active' => true,
        ]);

        $garageCar = GarageCar::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'name' => 'My Audi A6',
            'image_url' => 'garage/custom_a6.jpg',
            'tracking_code' => 'GC-12345',
            'warranty_active' => true,
        ]);

        $api = $garageCar->toApi();
        $this->assertSame('/media/garage/custom_a6.jpg', $api['vehicle']['image_url']);
        $this->assertStringNotContainsString('127.0.0.1', $api['vehicle']['image_url']);
    }
}
