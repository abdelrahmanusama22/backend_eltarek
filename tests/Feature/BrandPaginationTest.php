<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_detail_bounds_models_and_exposes_next_cursor(): void
    {
        $brand = Brand::create(['name' => 'Test', 'name_ar' => 'تجربة', 'active' => true]);
        foreach (range(1, 3) as $index) {
            Vehicle::create([
                'brand_id' => $brand->id, 'model' => "Model {$index}",
                'model_ar' => "موديل {$index}", 'year' => 2026,
                'category' => 'Sedan', 'starting_price_egp' => 1000, 'active' => true,
            ]);
        }

        $first = $this->getJson("/api/v1/brands/{$brand->id}?limit=2")
            ->assertOk()->assertJsonCount(2, 'data.models');
        $cursor = $first->json('meta.next_cursor');
        $this->assertNotNull($cursor);
        $second = $this->getJson("/api/v1/brands/{$brand->id}?limit=2&cursor=".urlencode($cursor))
            ->assertOk()->assertJsonCount(1, 'data.models');
        $this->assertNotSame($first->json('data.models.0.id'), $second->json('data.models.0.id'));
    }
}
