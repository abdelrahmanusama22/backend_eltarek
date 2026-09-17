<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$page = \App\Models\Trim::where('active', true)
    ->whereHas('vehicle', fn ($query) => $query->where('active', true))
    ->orderBy('id')->cursorPaginate(100);

echo "Total items in first page: " . count($page->items()) . "\n";
echo "Has more pages: " . ($page->hasMorePages() ? 'Yes' : 'No') . "\n";
$trim497 = collect($page->items())->firstWhere('id', 497);
echo "Trim 497 found: " . ($trim497 ? 'Yes' : 'No') . "\n";
