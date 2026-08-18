<?php

namespace App\Http\Controllers\Api;

use App\Models\AppSetting;
use App\Models\Brand;
use App\Models\Trim;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $heroes = Vehicle::where('active', true)
            ->whereNotNull('badge')
            ->orderBy('sort')
            ->get()
            ->map(fn (Vehicle $v) => [
                'id' => $v->id,
                'vehicle_id' => $v->id,
                'title' => $v->model,
                'title_ar' => $v->model_ar,
                'subtitle' => "{$v->engine_summary} • {$v->year}",
                'badge' => $v->badge,
                'image_url' => $v->image_url,
                'cta_label' => 'Explore',
            ]);

        $smartMatches = null;
        if ($user) {
            $config = collect(AppSetting::get('smart_matches', []));
            $trims = Trim::with('vehicle')->findMany($config->pluck('trim_id'));
            $smartMatches = $config->map(function (array $match) use ($trims) {
                $trim = $trims->firstWhere('id', $match['trim_id']);
                if (! $trim) {
                    return null;
                }

                return [
                    'trim_id' => $trim->id,
                    'vehicle_id' => $trim->vehicle_id,
                    'match_percentage' => $match['match_percentage'],
                    'name' => $trim->vehicle->model,
                    'name_ar' => $trim->vehicle->model_ar,
                    'engine_summary' => $trim->vehicle->engine_summary,
                    'image_url' => $trim->vehicle->image_url,
                    'price_egp' => $trim->price_egp,
                ];
            })->filter()->values();
        }

        $budgetIds = AppSetting::get('budget_pick_trim_ids', []);
        $budgetTrims = Trim::with('vehicle')->findMany($budgetIds)
            ->sortBy(fn (Trim $t) => array_search($t->id, $budgetIds))
            ->values()
            ->map(fn (Trim $t) => [
                'trim_id' => $t->id,
                'vehicle_id' => $t->vehicle_id,
                'name' => $t->vehicle->model,
                'name_ar' => $t->vehicle->model_ar,
                'price_egp' => $t->price_egp,
                'monthly_from_egp' => $t->vehicle->monthly_from_egp,
                'image_url' => $t->vehicle->image_url,
            ]);

        return $this->ok([
            'hero_banners' => $heroes,
            'brands' => Brand::where('active', true)->orderBy('sort')->get()->map->toApi(),
            'smart_matches' => $smartMatches,
            'budget_picks' => array_merge(
                AppSetting::get('budget_section', []),
                ['vehicles' => $budgetTrims],
            ),
            'financing_banner' => AppSetting::get('financing_banner'),
        ]);
    }
}
