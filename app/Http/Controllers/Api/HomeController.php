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
        $version = CatalogEvents::version();
        $cacheKey = self::CACHE_KEY.':'.$version;
        $payload = Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($version) {
            $configuredHeroIds = AppSetting::get('home_hero_vehicle_ids');
            $heroIds = Vehicle::published()
                ->whereIn('id', $configuredHeroIds ?? [])
                ->pluck('id')->values();
            $heroQuery = Vehicle::with('trims')
                ->published()
                ->when($configuredHeroIds !== null, fn ($query) => $query->whereIn('id', $heroIds), fn ($query) => $query->whereNotNull('badge')->orderBy('sort'));
            $heroes = $heroQuery->take(5)->get()
                ->when($heroIds->isNotEmpty(), fn ($items) => $items->sortBy(fn (Vehicle $vehicle) => $heroIds->search($vehicle->id)))
                ->values()
                ->map->toApi()
                ->values()
                ->all();

            $brands = Brand::published()
                ->orderBy('sort')
                ->get()
                ->map->toApi()
                ->values()
                ->all();

            $configuredMatchRows = AppSetting::get('smart_matches');
            $configuredMatches = collect($configuredMatchRows ?? [])
                ->filter(fn ($item) => is_array($item) && ! empty($item['trim_id']))->values();
            $smartIds = Trim::published()
                ->whereIn('id', $configuredMatches->pluck('trim_id'))
                ->pluck('id')->sortBy(fn ($id) => $configuredMatches->pluck('trim_id')->search($id))->values();
            $smartMatches = Trim::published()
                ->with(['vehicle' => function ($query) {
                    $query->select('id', 'image_url', 'model', 'model_ar', 'engine_summary');
                }])
                ->when($configuredMatchRows !== null, fn ($query) => $query->whereIn('id', $smartIds), fn ($query) => $query->orderByDesc('is_most_popular')->orderBy('price_egp'))
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

            $configuredBudgetRows = AppSetting::get('budget_pick_trim_ids');
            $configuredBudgetIds = collect($configuredBudgetRows ?? [])->map(fn ($id) => (int) $id)->filter()->values();
            $budgetIds = Trim::published()
                ->whereIn('id', $configuredBudgetIds)
                ->pluck('id')->sortBy(fn ($id) => $configuredBudgetIds->search($id))->values();
            $budgetPicks = Trim::published()
                ->with(['vehicle' => function ($query) {
                    $query->select('id', 'image_url', 'model', 'model_ar', 'monthly_from_egp');
                }])
                ->whereNotNull('price_egp')
                ->when($configuredBudgetRows !== null, fn ($query) => $query->whereIn('id', $budgetIds), fn ($query) => $query->orderBy('price_egp', 'asc'))
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
                'catalog_version' => $version,
                'heroes' => $heroes,
                'brands' => $brands,
                'smart_matches' => $smartMatches,
                'budget_picks' => $budgetPicks,
                'financing_banner' => AppSetting::get('financing_banner'),
            ];
        });

        if (CatalogEvents::version() !== $version) {
            Cache::forget($cacheKey);
            $attempt = (int) $request->attributes->get('home_version_attempt', 0);
            if ($attempt >= 2) {
                return $this->fail('Home content changed during loading. Retry.', 503);
            }
            $request->attributes->set('home_version_attempt', $attempt + 1);
            return $this->index($request);
        }

        return $this->ok($payload)->header('Cache-Control', 'no-store');
    }
}
