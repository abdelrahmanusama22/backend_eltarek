<?php

namespace Tests\Feature;

use App\Jobs\ProcessCatalogImportJob;
use App\Models\CatalogImportRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class CatalogImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_is_idempotent_atomic_and_reports_rejected_rows(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $placeholder = tempnam(sys_get_temp_dir(), 'catalog-');
        $path = $placeholder.'.xlsx';
        unlink($placeholder);
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['Brand', 'Model', 'Year', 'Category', 'Official Price']));
        $writer->addRow(Row::fromValues(['Acme', 'Roadster', '2026', 'Premium', '1000000']));
        $writer->addRow(Row::fromValues(['Acme', 'Roadster', '2026', 'Premium', '1100000']));
        $writer->addRow(Row::fromValues(['Acme', 'Broken', '1800', 'Base', '1']));
        $writer->close();
        $run = CatalogImportRun::create([
            'user_id' => $user->id,
            'file_name' => basename($path),
            'status' => 'queued',
        ]);

        (new ProcessCatalogImportJob($path, ['price_egp'], $user->id, $run->id))->handle();

        $this->assertDatabaseCount('brands', 1);
        $this->assertDatabaseCount('vehicles', 1);
        $this->assertDatabaseCount('trims', 1);
        $this->assertDatabaseHas('trims', ['name' => 'Premium', 'price_egp' => 1100000]);
        $this->assertDatabaseHas('catalog_import_runs', [
            'id' => $run->id,
            'status' => 'completed',
            'processed_rows' => 2,
            'rejected_rows' => 1,
        ]);
        $this->assertFileDoesNotExist($path);
    }
}
