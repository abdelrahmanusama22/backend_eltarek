<?php

namespace App\Http\Controllers\Api;

use App\Models\Brand;
use App\Models\Trim;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $heroes = Vehicle::whereNotNull('badge')
            ->whereBetween('year', [now()->year - 1, now()->year + 1])
            ->select('id', 'brand_id', 'image_url', 'badge', 'model', 'model_ar', 'engine_summary', 'year')
            ->inRandomOrder()
            ->take(5)
            ->get();

        $brands = Brand::where('active', true)
            ->orderBy('sort')
            ->get()
            ->map->toApi();

        $smartMatches = Trim::whereHas('vehicle', function ($q) {
                $q->whereBetween('year', [now()->year - 1, now()->year + 1]);
            })
            ->with(['vehicle' => function ($query) {
                $query->select('id', 'image_url', 'model', 'model_ar', 'engine_summary');
            }])
            ->inRandomOrder()
            ->take(4)
            ->get()
            ->map(function ($trim) {
                return [
                    'match_percentage' => rand(85, 99),
                    'trim' => [
                        'id' => $trim->id,
                        'price_egp' => $trim->price_egp,
                    ],
                    'vehicle' => $trim->vehicle ? [
                        'id' => $trim->vehicle->id,
                        'image_url' => $trim->vehicle->image_url,
                        'model' => $trim->vehicle->model,
                        'model_ar' => $trim->vehicle->model_ar,
                        'engine_summary' => $trim->vehicle->engine_summary,
                    ] : null,
                ];
            });

        $budgetPicks = Trim::whereHas('vehicle', function ($q) {
                $q->whereBetween('year', [now()->year - 1, now()->year + 1]);
            })
            ->with(['vehicle' => function ($query) {
                $query->select('id', 'image_url', 'model', 'model_ar', 'monthly_from_egp');
            }])
            ->whereNotNull('price_egp')
            ->orderBy('price_egp', 'asc')
            ->take(5)
            ->get()
            ->map(function ($trim) {
                return [
                    'trim' => [
                        'id' => $trim->id,
                        'price_egp' => $trim->price_egp,
                    ],
                    'vehicle' => $trim->vehicle ? [
                        'id' => $trim->vehicle->id,
                        'image_url' => $trim->vehicle->image_url,
                        'model' => $trim->vehicle->model,
                        'model_ar' => $trim->vehicle->model_ar,
                        'monthly_from_egp' => $trim->vehicle->monthly_from_egp,
                    ] : null,
                ];
            });

        return response()->json([
            'data' => [
                'heroes' => $heroes,
                'brands' => $brands,
                'smart_matches' => $smartMatches,
                'budget_picks' => $budgetPicks,
            ]
        ]);
    }
}
