<?php

namespace App\Http\Controllers\Api;

use App\Models\Brand;
use App\Models\Trim;
use App\Models\Vehicle;
use App\Support\CatalogEvents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends ApiController
{
    public function version(): JsonResponse
    {
        return $this->ok(['version' => CatalogEvents::version()]);
    }

    public function brands(): JsonResponse
    {
        return $this->ok(
            Brand::where('active', true)->orderBy('sort')->get()->map->toApi()->values()->all(),
            meta: ['catalog_version' => CatalogEvents::version()],
        );
    }

    public function vehicles(Request $request): JsonResponse
    {
        $limit = min(100, max(10, $request->integer('limit', 50)));
        $page = Vehicle::with('trims')->where('active', true)->orderBy('id')->cursorPaginate($limit);

        return $this->ok(collect($page->items())->map(fn (Vehicle $v) => $v->toApi(includeTrims: false))->values()->all(), meta: [
            'catalog_version' => CatalogEvents::version(),
            'next_cursor' => $page->nextCursor()?->encode(),
            'has_more' => $page->hasMorePages(),
        ]);
    }

    public function trims(Request $request): JsonResponse
    {
        $limit = min(200, max(20, $request->integer('limit', 100)));
        $page = Trim::where('active', true)
            ->whereHas('vehicle', fn ($query) => $query->where('active', true))
            ->orderBy('id')->cursorPaginate($limit);

        return $this->ok(collect($page->items())->map->toApi()->values()->all(), meta: [
            'catalog_version' => CatalogEvents::version(),
            'next_cursor' => $page->nextCursor()?->encode(),
            'has_more' => $page->hasMorePages(),
        ]);
    }
}
