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

        $branches = $query->get();

        if ($lat !== null && $lng !== null) {
            $branches = $branches->sortBy(fn (Branch $b) => $b->distanceKm($lat, $lng))->values();
        }

        return $this->ok($branches->map(fn (Branch $b) => $b->toApi($lat, $lng)));
    }

    public function show(Branch $branch): JsonResponse
    {
        abort_unless($branch->active, 404);
        return $this->ok($branch->toApi());
    }
}
