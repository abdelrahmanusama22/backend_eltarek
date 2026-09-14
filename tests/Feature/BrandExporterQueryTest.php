<?php

namespace Tests\Feature;

use App\Filament\Exports\BrandExporter;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BrandExporterQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_exported_vehicle_counts_do_not_add_one_query_per_brand(): void
    {
        foreach (range(1, 50) as $index) {
            DB::table('brands')->insert([
                'name' => "Brand {$index}", 'name_ar' => "علامة {$index}", 'active' => true,
            ]);
        }

        DB::enableQueryLog();
        DB::flushQueryLog();
        $rows = BrandExporter::modifyQuery(Brand::query())->get();
        foreach ($rows as $row) {
            $this->assertSame(0, $row->vehicles_count);
        }
        $selects = collect(DB::getQueryLog())->filter(
            fn (array $query) => str_starts_with(strtolower($query['query']), 'select'),
        );
        $this->assertCount(1, $selects);
    }
}
