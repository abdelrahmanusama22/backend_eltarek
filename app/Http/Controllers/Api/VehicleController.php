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
            'category' => ['nullable', 'in:SUV,Sedan,Electric,Coupe'],
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

        $page = $query->paginate($request->integer('per_page') ?: 15);

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
        $request->validate(['q' => ['required', 'string', 'min:1']]);
        $q = $request->string('q')->toString();

        $vehicles = Vehicle::with(['brand', 'trims'])
            ->where('active', true)
            ->where(function ($query) use ($q) {
                $query->where('model', 'like', "%{$q}%")
                    ->orWhere('model_ar', 'like', "%{$q}%")
                    ->orWhere('year', 'like', "%{$q}%")
                    ->orWhereHas('brand', fn ($b) => $b
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('name_ar', 'like', "%{$q}%"));
            })
            ->limit(30)
            ->get();

        return $this->ok(
            $vehicles->map(fn (Vehicle $v) => $this->vehicleListItem($v)),
            meta: ['current_page' => 1, 'total' => $vehicles->count()],
        );
    }

    /** GET /vehicles/{vehicle}/trims */
    public function trims(Vehicle $vehicle): JsonResponse
    {
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
        $user = $request->user('sanctum');
        $vehicle = $trim->vehicle;
        $rival = $trim->suggested_comparison_trim_id
            ? Trim::with('vehicle')->find($trim->suggested_comparison_trim_id)
            : null;

        // "BMW X5 M50i" + trim "M50i" ??? keep "BMW X5 M50i", not "??? M50i M50i".
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
        $user = $request->user();
        $isFavorited = $user->favorites()->whereKey($trim->id)->exists();
        $user->favorites()->toggle($trim->id);

        return $this->ok([
            'is_favorited' => ! $isFavorited,
            'message' => $isFavorited ? 'Removed from favorites.' : 'Added to favorites.',
        ]);
    }

    private function vehicleListItem(Vehicle $vehicle): array
    {
        return array_merge($vehicle->toApi(), [
            'brand' => $vehicle->brand->toApi(),
            'trims_count' => $vehicle->trims->count(),
            'primary_trim_id' => $vehicle->trims->first()?->id,
            'availability' => 'available',
        ]);
    }
}
