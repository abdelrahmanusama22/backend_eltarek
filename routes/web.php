<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::match(['GET', 'HEAD', 'OPTIONS'], '/media/{path}', function ($path) {
    if (request()->getMethod() === 'OPTIONS') {
        return response('', 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS')
            ->header('Access-Control-Allow-Headers', '*');
    }

    $basePath = realpath(storage_path('app/public'));
    $filePath = storage_path('app/public/' . $path);
    $realPath = realpath($filePath);

    if (! $basePath || ! $realPath || ! str_starts_with($realPath, $basePath) || ! is_file($realPath)) {
        abort(404);
    }

    return response()->file($realPath, [
        'Access-Control-Allow-Origin'  => '*',
        'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
        'Cache-Control'                => 'public, max-age=31536000, immutable',
    ]);
})->where('path', '.*')->withoutMiddleware(['web', \Illuminate\Session\Middleware\StartSession::class]);

