<?php

namespace App\Http\Controllers\Api;

use App\Models\Trim;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends ApiController
{
    /** GET /vehicles ??? filtered, paginated catalog. */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'category' => ['nullable', 'in:SUV,Sedan,Electric,Coupe,Hatchback,Pickup,Van,Other'],
            'sort' => ['nullable', 'in:price_asc,price_desc,newest'],
        ]);

        $query = Vehicle::with(['brand', 'trims'])->where('active', true);

        if ($brandId = $request->integer('brand_id')) {
            $query->where('brand_id', $brandId);
        }
        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }
        if ($min = $request->integer('min_price')) {
            $query->where('starting_price_egp', '>=', $min);
        }
        if ($max = $request->integer('max_price')) {
            $query->where('starting_price_egp', '<=', $max);
        }
        match ($request->string('sort')->toString()) {
            'price_asc' => $query->orderBy('starting_price_egp'),
            'price_desc' => $query->orderByDesc('starting_price_egp'),
            'newest' => $query->orderByDesc('year'),
            default => $query->orderBy('sort'),
        };

        $perPage = min(max(1, $request->integer('per_page') ?: 15), 50);
        $page = $query->paginate($perPage);

        return $this->ok(
            collect($page->items())->map(fn (Vehicle $v) => $this->vehicleListItem($v)),
            meta: [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
        );
    }

    /** GET /vehicles/search?q= */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:1', 'max:80']]);
        $q = $request->string('q')->toString();
        $terms = $this->vehicleSearchTerms($q);

        $vehicles = Vehicle::with(['brand', 'trims'])
            ->where('active', true)
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
                    $query->orWhere('model', 'like', "%{$escaped}%")
                        ->orWhere('model_ar', 'like', "%{$escaped}%")
                        ->orWhere('year', 'like', "%{$escaped}%")
                        ->orWhereHas('brand', fn ($brand) => $brand
                            ->where('name', 'like', "%{$escaped}%")
                            ->orWhere('name_ar', 'like', "%{$escaped}%"));
                }
            })
            ->limit(30)
            ->get();

        return $this->ok(
            $vehicles->map(fn (Vehicle $v) => $this->vehicleListItem($v)),
            meta: ['current_page' => 1, 'total' => $vehicles->count()],
        );
    }

    /** @return list<string> */
    private function vehicleSearchTerms(string $query): array
    {
        $aliases = [
            'مرسيدس' => 'mercedes', 'بي ام دبليو' => 'bmw',
            'اودي' => 'audi', 'تويوتا' => 'toyota', 'كيا' => 'kia',
            'هيونداي' => 'hyundai', 'بورشه' => 'porsche', 'تسلا' => 'tesla',
            'نيسان' => 'nissan', 'شيفروليه' => 'chevrolet', 'شيري' => 'chery',
            'بي واي دي' => 'byd', 'شانجان' => 'changan', 'رينو' => 'renault',
            'بيجو' => 'peugeot', 'فولكس فاجن' => 'volkswagen', 'سيات' => 'seat',
            'سكودا' => 'skoda', 'سوزوكي' => 'suzuki', 'ميتسوبيشي' => 'mitsubishi',
            'اوبل' => 'opel', 'فيات' => 'fiat', 'فورد' => 'ford', 'جيب' => 'jeep',
            'لاند روفر' => 'land rover', 'رينج روفر' => 'range rover',
            'ام جي' => 'mg', 'جي اي سي' => 'gac', 'هافال' => 'haval',
            'جيتور' => 'jetour', 'سيتروين' => 'citroen', 'كوبرا' => 'cupra',
            'ديبال' => 'deepal',
        ];

        $normalized = $this->normalizeArabicSearch($query);
        $terms = [$query, $normalized];
        foreach ($aliases as $alias => $english) {
            $normalizedAlias = $this->normalizeArabicSearch($alias);
            if (str_contains($normalizedAlias, $normalized) || str_contains($normalized, $normalizedAlias)) {
                $terms[] = $english;
            }
        }

        return array_values(array_unique(array_filter($terms)));
    }

    private function normalizeArabicSearch(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $value) ?? $value;
        $value = str_replace(['آ', 'أ', 'إ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ه'], $value);

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    /** GET /vehicles/{vehicle}/trims */
    public function trims(Vehicle $vehicle): JsonResponse
    {
        abort_unless($vehicle->active, 404);

        return $this->ok([
            'vehicle_id' => $vehicle->id,
            'model' => $vehicle->model,
            'model_ar' => $vehicle->model_ar,
            'year' => $vehicle->year,
            'hero_image_url' => $vehicle->resolved_image_url,
            'base_price_egp' => $vehicle->starting_price_egp,
            'trims' => $vehicle->trims->map->toApi(),
        ]);
    }

    /** GET /trims/{trim} */
    public function trimDetail(Request $request, Trim $trim): JsonResponse
    {
        abort_unless($trim->active && $trim->vehicle?->active, 404);

        $user = $request->user('sanctum');
        $vehicle = $trim->vehicle;
        $rival = $trim->suggested_comparison_trim_id
            ? Trim::with('vehicle')->find($trim->suggested_comparison_trim_id)
            : null;

        // "BMW X5 M50i" + trim "M50i" => keep "BMW X5 M50i", not "BMW X5 M50i M50i".
        $displayName = str_ends_with($vehicle->model, $trim->name)
            ? $vehicle->model
            : trim("{$vehicle->model} {$trim->name}");

        return $this->ok(array_merge($trim->toApi(), [
            'name' => $displayName,
            'year' => $vehicle->year,
            'availability' => 'available',
            'is_favorited' => $user !== null && $user->favorites()->whereKey($trim->id)->exists(),
            'suggested_comparison' => $rival ? [
                'trim_id' => $rival->id,
                'name' => $rival->vehicle->model,
                'name_ar' => $rival->vehicle->model_ar,
            ] : null,
        ]));
    }

    /** POST /trims/{trim}/favorite */
    public function toggleFavorite(Request $request, Trim $trim): JsonResponse
    {
        abort_unless($trim->active && $trim->vehicle?->active, 404);

        $user = $request->user();
        $isFavorited = $user->favorites()->whereKey($trim->id)->exists();
        $user->favorites()->toggle($trim->id);

        return $this->ok([
            'is_favorited' => ! $isFavorited,
            'message' => $isFavorited ? 'Removed from favorites.' : 'Added to favorites.',
        ]);
    }

    /** GET /vehicles/{vehicle} */
    public function show($id): JsonResponse
    {
        $vehicle = Vehicle::with(['brand', 'trims'])->where('active', true)->findOrFail($id);

        return $this->ok($this->vehicleListItem($vehicle));
    }

    private function vehicleListItem(Vehicle $vehicle): array
    {
        return array_merge($vehicle->toApi(includeTrims: false), [
            'brand' => $vehicle->brand?->toApi() ?? ['id' => $vehicle->brand_id, 'name' => ''],
            'trims_count' => $vehicle->trims->count(),
            'primary_trim_id' => $vehicle->trims->first()?->id,
            'availability' => 'available',
            'trims' => $vehicle->trims->map->toApi()->toArray(),
        ]);
    }
}
