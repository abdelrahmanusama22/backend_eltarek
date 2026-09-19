<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class ShowroomController extends Controller
{
    public function filter(Request $request)
    {
        $query = Vehicle::query()
            ->where('active', 1)
            ->with(['trims' => function ($q) use ($request) {
                $q->where('active', 1);
                if ($request->filled('max_price')) {
                    $q->where('price_egp', '<=', $request->max_price);
                }
            }])
            ->whereHas('trims', function ($q) use ($request) {
                $q->where('active', 1);
                if ($request->filled('max_price')) {
                    $q->where('price_egp', '<=', $request->max_price);
                }
            });

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $vehicles = $query->get()->map->toApi(true);

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ]);
    }
}