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
                ->where('active', true)
                ->orderBy('sort')
                ->get()
                ->map(fn (Brand $brand) => array_merge($brand->toApi(), [
                    'vehicle_count' => $brand->vehicles_count,
                ])),
        );
    }

    public function show(Request $request, Brand $brand): JsonResponse
    {
        $allVehicles = $brand->vehicles()->with('trims')->get();
        $categories = array_values(array_unique(array_merge(
            ['All'],
            $allVehicles->pluck('category')->filter()->unique()->values()->all(),
        )));

        $models = $allVehicles;
        if ($category = $request->string('category')->toString()) {
            if ($category !== 'All') {
                $models = $models->where('category', $category)->values();
            }
        }

        return $this->ok(array_merge($brand->toApi(), [
            'categories' => $categories,
            'models' => $models->map(fn ($v) => array_merge($v->toApi(), [
                'trims_count' => $v->trims->count(),
                'primary_trim_id' => $v->trims->first()?->id,
            ])),
        ]));
    }
}
