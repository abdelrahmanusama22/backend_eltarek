<?php

namespace App\Support;

use App\Events\CatalogUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class CatalogEvents
{
    private static bool $suppressed = false;

    public static function suppress(): void
    {
        self::$suppressed = true;
    }

    public static function resume(): void
    {
        self::$suppressed = false;
    }

    public static function broadcast(string $message = 'Catalog updated'): void
    {
        if (! self::$suppressed) {
            Cache::forever('catalog:version', now()->format('YmdHis').'-'.Str::lower(Str::random(8)));
            Cache::forget('api:v1:bootstrap:public:v2');
            event(new CatalogUpdated($message));
        }
    }

    public static function version(): string
    {
        return Cache::rememberForever(
            'catalog:version',
            fn (): string => now()->format('YmdHis').'-'.Str::lower(Str::random(8)),
        );
    }
}
