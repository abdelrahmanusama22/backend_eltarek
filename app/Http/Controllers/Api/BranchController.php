<?php

namespace App\Http\Controllers\Api;

use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $lat = $request->filled('lat') ? (float) $request->input('lat') : null;
        $lng = $request->filled('lng') ? (float) $request->input('lng') : null;

        $branches = Branch::where('active', true)->get();

        if ($q = $request->string('q')->toString()) {
            $branches = $branches->filter(fn (Branch $b) => str_contains(strtolower($b->name), strtolower($q))
                || str_contains($b->name_ar, $q)
                || str_contains(strtolower($b->address), strtolower($q))
                || str_contains($b->address_ar, $q))->values();
        }

        if ($lat !== null && $lng !== null) {
            $branches = $branches->sortBy(fn (Branch $b) => $b->distanceKm($lat, $lng))->values();
        }

        return $this->ok($branches->map(fn (Branch $b) => $b->toApi($lat, $lng)));
    }

    public function show(Branch $branch): JsonResponse
    {
        return $this->ok($branch->toApi());
    }
}
