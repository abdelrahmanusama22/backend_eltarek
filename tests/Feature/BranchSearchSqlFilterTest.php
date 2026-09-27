<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BranchSearchSqlFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_search_executes_in_sql_query(): void
    {
        $cairo = City::create(['name' => 'Cairo', 'name_ar' => 'القاهرة', 'sort' => 1]);
        $alex = City::create(['name' => 'Alexandria', 'name_ar' => 'الإسكندرية', 'sort' => 2]);

        $nasrCity = Branch::create([
            'city_id' => $cairo->id,
            'name' => 'Nasr City Showroom',
            'name_ar' => 'معرض مدينة نصر',
            'address' => 'Makram Ebeid, Nasr City',
            'address_ar' => 'مكرم عبيد، مدينة نصر',
            'phone' => '19001',
            'hours' => '9 AM - 10 PM',
            'hours_ar' => '9 ص - 10 م',
            'lat' => 30.0500,
            'lng' => 31.3300,
            'active' => true,
        ]);

        $maadi = Branch::create([
            'city_id' => $cairo->id,
            'name' => 'Maadi Branch',
            'name_ar' => 'فرع المعادي',
            'address' => 'Degla, Maadi',
            'address_ar' => 'دجلة، المعادي',
            'phone' => '19002',
            'hours' => '9 AM - 10 PM',
            'hours_ar' => '9 ص - 10 م',
            'lat' => 29.9600,
            'lng' => 31.2800,
            'active' => true,
        ]);

        $alexBranch = Branch::create([
            'city_id' => $alex->id,
            'name' => 'Gleem Showroom',
            'name_ar' => 'معرض جليم',
            'address' => 'Corniche, Gleem',
            'address_ar' => 'طريق الكورنيش، جليم',
            'phone' => '19003',
            'hours' => '9 AM - 10 PM',
            'hours_ar' => '9 ص - 10 م',
            'lat' => 31.2300,
            'lng' => 29.9600,
            'active' => true,
        ]);

        // 1. Search by English name keyword
        DB::enableQueryLog();
        DB::flushQueryLog();

        $res1 = $this->getJson('/api/v1/branches?q=Nasr')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $nasrCity->id);

        $queries = DB::getQueryLog();
        $selectQuery = collect($queries)->first(fn ($q) => str_contains(strtolower($q['query']), 'select'));
        $this->assertNotNull($selectQuery);
        $this->assertStringContainsString('like', strtolower($selectQuery['query']));

        // 2. Search by Arabic name keyword
        $res2 = $this->getJson('/api/v1/branches?q='.urlencode('المعادي'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $maadi->id);

        // 3. Search by Address keyword
        $res3 = $this->getJson('/api/v1/branches?q=Corniche')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $alexBranch->id);

        // 4. Combined with city filter
        $res4 = $this->getJson("/api/v1/branches?city_id={$alex->id}&q=Nasr")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_branch_results_are_bounded_and_have_a_next_cursor(): void
    {
        $city = City::create(['name' => 'Cairo', 'name_ar' => 'القاهرة', 'sort' => 1]);
        foreach (range(1, 4) as $index) {
            Branch::create([
                'city_id' => $city->id, 'name' => "Branch {$index}", 'name_ar' => "فرع {$index}",
                'address' => 'Address', 'address_ar' => 'عنوان', 'phone' => '19001',
                'hours' => '9 AM - 10 PM', 'hours_ar' => '9 ص - 10 م',
                'lat' => 30.0 + $index / 100, 'lng' => 31.0, 'active' => true,
            ]);
        }

        $first = $this->getJson('/api/v1/branches?limit=2')->assertOk()->assertJsonCount(2, 'data');
        $cursor = $first->json('meta.next_cursor');
        $this->assertNotNull($cursor);
        $second = $this->getJson('/api/v1/branches?limit=2&cursor='.urlencode($cursor))
            ->assertOk()->assertJsonCount(2, 'data');
        $this->assertNotSame($first->json('data.0.id'), $second->json('data.0.id'));

        $nearby = $this->getJson('/api/v1/branches?lat=30.0&lng=31.0&limit=2')
            ->assertOk()->assertJsonCount(2, 'data');
        $this->assertLessThanOrEqual(
            $nearby->json('data.1.distance_km'), $nearby->json('data.0.distance_km'),
        );
    }
}
