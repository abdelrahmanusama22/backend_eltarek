<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = \Illuminate\Support\Facades\Schema::getColumns('trims');
foreach($columns as $c) {
    echo $c['name'] . " - " . $c['type_name'] . " - " . ($c['nullable'] ? "NULLABLE" : "NOT NULL") . "\n";
}
