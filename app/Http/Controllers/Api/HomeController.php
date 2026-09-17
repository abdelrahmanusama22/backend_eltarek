<?php

namespace App\Http\Controllers\Api;

use App\Models\AppSetting;
use App\Models\Brand;
use App\Models\Trim;
use App\Models\Vehicle;
use App\Support\CatalogEvents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends ApiController
{
    public const CACHE_KEY = 'api:v1:home:payload';

    public const CACHE_TTL_SECONDS = 3600;

    public static function cacheKey(): string
    {
        return self::CACHE_KEY.':'.CatalogEvents::version();
    }

    public function index(Request $request): JsonResponse
    {
        $payload = Cache::remember(self::cacheKey(), now()->addSeconds(self::CACHE_TTL_SECONDS), function () {
            $heroIds = Vehicle::where('active', true)
                ->whereIn('id', AppSetting::get('home_hero_vehicle_ids', []))
                ->pluck('id')->values();
            $heroQuery = Vehicle::with('trims')
                ->where('active', true)
                ->when($heroIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $heroIds), fn ($query) => $query->whereNotNull('badge')->orderBy('sort'));
            $heroes = $heroQuery->take(5)->get()
                ->when($heroIds->isNotEmpty(), fn ($items) => $items->sortBy(fn (Vehicle $vehicle) => $heroIds->search($vehicle->id)))
                ->values()
                ->map->toApi()
                ->values()
                ->all();

            $brands = Brand::where('active', true)
                ->orderBy('sort')
                ->get()
                ->map->toApi()
                ->values()
                ->all();

            $configuredMatches = collect(AppSetting::get('smart_matches', []))
                ->filter(fn ($item) => is_array($item) && ! empty($item['trim_id']))->values();
            $smartIds = Trim::where('active', true)
                ->whereHas('vehicle', fn ($query) => $query->where('active', true))
                ->whereIn('id', $configuredMatches->pluck('trim_id'))
                ->pluck('id')->sortBy(fn ($id) => $configuredMatches->pluck('trim_id')->search($id))->values();
            $smartMatches = Trim::whereHas('vehicle', function ($q) {
                $q->where('active', true);
            })
                ->where('active', true)
                ->with(['vehicle' => function ($query) {
                    $query->select('id', 'image_url', 'model', 'model_ar', 'engine_summary');
                }])
                ->when($smartIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $smartIds), fn ($query) => $query->orderByDesc('is_most_popular')->orderBy('price_egp'))
                ->take(8)
                ->get()
                ->when($smartIds->isNotEmpty(), fn ($items) => $items->sortBy(fn (Trim $trim) => $smartIds->search($trim->id)))
                ->values()
                ->map(function ($trim) use ($configuredMatches) {
                    $configured = $configuredMatches->firstWhere('trim_id', $trim->id);

                    return [
                        'match_percentage' => (int) ($configured['match_percentage'] ?? 0),
                        'trim' => $trim->toApi(),
                        'vehicle' => $trim->vehicle ? [
                            'id' => $trim->vehicle->id,
                            'image_url' => $trim->vehicle->resolved_image_url,
                            'model' => $trim->vehicle->model,
                            'model_ar' => $trim->vehicle->model_ar,
                            'engine_summary' => $trim->vehicle->engine_summary,
                        ] : null,
                    ];
                })
                ->values()
                ->all();

            $configuredBudgetIds = collect(AppSetting::get('budget_pick_trim_ids', []))->map(fn ($id) => (int) $id)->filter()->values();
            $budgetIds = Trim::where('active', true)
                ->whereHas('vehicle', fn ($query) => $query->where('active', true))
                ->whereIn('id', $configuredBudgetIds)
                ->pluck('id')->sortBy(fn ($id) => $configuredBudgetIds->search($id))->values();
            $budgetPicks = Trim::whereHas('vehicle', function ($q) {
                $q->where('active', true);
            })
                ->where('active', true)
                ->with(['vehicle' => function ($query) {
                    $query->select('id', 'image_url', 'model', 'model_ar', 'monthly_from_egp');
                }])
                ->whereNotNull('price_egp')
                ->when($budgetIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $budgetIds), fn ($query) => $query->orderBy('price_egp', 'asc'))
                ->take(8)
                ->get()
                ->when($budgetIds->isNotEmpty(), fn ($items) => $items->sortBy(fn (Trim $trim) => $budgetIds->search($trim->id)))
                ->values()
                ->map(function ($trim) {
                    return [
                        'trim' => $trim->toApi(),
                        'vehicle' => $trim->vehicle ? [
                            'id' => $trim->vehicle->id,
                            'image_url' => $trim->vehicle->resolved_image_url,
                            'model' => $trim->vehicle->model,
                            'model_ar' => $trim->vehicle->model_ar,
                            'monthly_from_egp' => $trim->vehicle->monthly_from_egp,
                        ] : null,
                    ];
                })
                ->values()
                ->all();

            return [
                'catalog_version' => CatalogEvents::version(),
                'heroes' => $heroes,
                'brands' => $brands,
                'smart_matches' => $smartMatches,
                'budget_picks' => $budgetPicks,
                'financing_banner' => AppSetting::get('financing_banner'),
            ];
        });

        return $this->ok($payload)->header('Cache-Control', 'public, max-age=120');
    }
}
