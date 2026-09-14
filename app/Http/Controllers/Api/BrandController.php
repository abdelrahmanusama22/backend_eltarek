<?php

namespace App\Http\Controllers\Api;

use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->ok(
            Brand::withCount('vehicles')
                ->published()
                ->orderBy('sort')
                ->get()
                ->map(fn (Brand $brand) => array_merge($brand->toApi(), [
                    'vehicle_count' => $brand->vehicles_count,
                ])),
        );
    }

    public function show(Request $request, Brand $brand): JsonResponse
    {
        abort_unless($brand->active, 404);
        $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $limit = min(50, max(1, $request->integer('limit', 20)));
        $categories = array_values(array_unique(array_merge(
            ['All'],
            $brand->vehicles()->distinct()->pluck('category')->filter()->values()->all(),
        )));

        $models = $brand->vehicles()->with('trims');
        if ($category = $request->string('category')->toString()) {
            if ($category !== 'All') {
                $models->where('category', $category);
            }
        }
        $page = $models->orderBy('id')->cursorPaginate($limit);

        return $this->ok(array_merge($brand->toApi(), [
            'categories' => $categories,
            'models' => collect($page->items())->map(fn ($v) => array_merge($v->toApi(), [
                'trims_count' => $v->trims->count(),
                'primary_trim_id' => $v->trims->first()?->id,
            ])),
        ]), meta: [
            'next_cursor' => $page->nextCursor()?->encode(),
            'has_more' => $page->hasMorePages(),
        ]);
    }
}
