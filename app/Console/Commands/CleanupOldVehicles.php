<?php

namespace App\Console\Commands;

use App\Models\Trim;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOldVehicles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-old-vehicles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete legacy vehicles (year < 2025), zero-price records, and orphaned vehicles.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting comprehensive cleanup of legacy and zero-price records...');

        try {
            DB::transaction(function () {
                $totalTrimsDeleted = 0;
                $totalVehiclesDeleted = 0;

                // 1. Delete zero-price trims
                $zeroPriceTrimsDeleted = Trim::where('price_egp', '<=', 0)->forceDelete();
                $totalTrimsDeleted += $zeroPriceTrimsDeleted;
                if ($zeroPriceTrimsDeleted > 0) {
                    $this->info("Deleted {$zeroPriceTrimsDeleted} zero-price trims.");
                }

                // 2. Target old or zero-price vehicles
                $vehicles = Vehicle::where('year', '<', 2025)->orWhere('starting_price_egp', '<=', 0)->get();
                $vehicleIds = $vehicles->pluck('id')->toArray();

                if (! empty($vehicleIds)) {
                    // 3. Delete associated trims
                    $associatedTrimsDeleted = Trim::whereIn('vehicle_id', $vehicleIds)->forceDelete();
                    $totalTrimsDeleted += $associatedTrimsDeleted;
                    if ($associatedTrimsDeleted > 0) {
                        $this->info("Deleted {$associatedTrimsDeleted} trims associated with targeted vehicles.");
                    }

                    // 4. Delete the targeted vehicles
                    $vehiclesDeleted = Vehicle::whereIn('id', $vehicleIds)->forceDelete();
                    $totalVehiclesDeleted += $vehiclesDeleted;
                    if ($vehiclesDeleted > 0) {
                        $this->info("Deleted {$vehiclesDeleted} targeted vehicles (year < 2025 or price <= 0).");
                    }
                }

                // 5. Clean orphaned vehicles (vehicles with 0 trims)
                $orphanedVehicles = Vehicle::doesntHave('trims')->get();
                $orphanedIds = $orphanedVehicles->pluck('id')->toArray();
                if (! empty($orphanedIds)) {
                    $orphanedDeleted = Vehicle::whereIn('id', $orphanedIds)->forceDelete();
                    $totalVehiclesDeleted += $orphanedDeleted;
                    if ($orphanedDeleted > 0) {
                        $this->info("Deleted {$orphanedDeleted} orphaned vehicles (0 trims).");
                    }
                }

                // 6. Output total counts
                $this->info("Cleanup complete! Total deleted: {$totalVehiclesDeleted} vehicles, {$totalTrimsDeleted} trims.");
            });
        } catch (\Exception $e) {
            $this->error('An error occurred during cleanup: '.$e->getMessage());
        }
    }
}
