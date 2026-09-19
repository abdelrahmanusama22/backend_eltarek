<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Google\Client as GoogleClient;

$mock = Mockery::mock(GoogleClient::class);
$app->bind(GoogleClient::class, function() use ($mock) { return clone $mock; });

$resolved = app(GoogleClient::class, ['config' => ['client_id' => 'abc']]);
var_dump($resolved instanceof \Mockery\MockInterface);
