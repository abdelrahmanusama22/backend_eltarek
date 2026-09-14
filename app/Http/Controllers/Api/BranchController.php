<?php

namespace App\Http\Controllers\Api;

use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $lat = $request->filled('lat') ? (float) $request->input('lat') : null;
        $lng = $request->filled('lng') ? (float) $request->input('lng') : null;
        $cityId = $request->filled('city_id') ? (int) $request->input('city_id') : null;

        $query = Branch::where('active', true);

        if ($cityId) {
            $query->where('city_id', $cityId);
        }

        if ($q = trim($request->string('q')->toString())) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('address_ar', 'like', "%{$q}%");
            });
        }

        if ($lat !== null && $lng !== null) {
            // Planar distance is sufficient to order nearby branches; the
            // precise Haversine distance is still returned by toApi().
            $longitudeWeight = cos(deg2rad($lat)) ** 2;
            $query->select('branches.*')->selectRaw(
                '((lat - ?) * (lat - ?) + (lng - ?) * (lng - ?) * ?) as distance_sort',
                [$lat, $lat, $lng, $lng, $longitudeWeight],
            )->orderBy('distance_sort');
        }
        $page = $query->orderBy('id')->cursorPaginate(min(100, max(1, $request->integer('limit', 50))));

        return $this->ok(collect($page->items())->map(fn (Branch $b) => $b->toApi($lat, $lng)), meta: [
            'next_cursor' => $page->nextCursor()?->encode(),
            'has_more' => $page->hasMorePages(),
        ]);
    }

    public function show(Branch $branch): JsonResponse
    {
        abort_unless($branch->active, 404);
        return $this->ok($branch->toApi());
    }
}
