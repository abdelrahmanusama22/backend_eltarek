<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$deletedTrims = App\Models\Trim::where('price_egp', '<', 200000)->delete();
echo "Deleted {$deletedTrims} trims with price < 200,000 EGP.\n";

$deletedVehiclesByPrice = App\Models\Vehicle::where('starting_price_egp', '<', 200000)->delete();
echo "Deleted {$deletedVehiclesByPrice} vehicles with starting price < 200,000 EGP.\n";

$deletedVehiclesByTrims = App\Models\Vehicle::doesntHave('trims')->delete();
echo "Deleted {$deletedVehiclesByTrims} vehicles with no trims.\n";
