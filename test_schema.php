<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$action = \Filament\Actions\ImportAction::make('test');
$components = $action->getFormSchema();

foreach ($components as $component) {
    if ($component instanceof \Filament\Forms\Components\FileUpload && $component->getName() === 'file') {
        echo "Found FileUpload! Modifying it...\n";
        $component->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}

// Now verify:
foreach ($action->getFormSchema() as $component) {
    if ($component instanceof \Filament\Forms\Components\FileUpload && $component->getName() === 'file') {
        var_dump($component->getAcceptedFileTypes());
    }
}
