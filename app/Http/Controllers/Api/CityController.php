<?php

namespace App\Http\Controllers\Api;

use App\Models\City;
use Illuminate\Http\JsonResponse;

class CityController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->ok(City::orderBy('sort')->get()->map->toApi());
    }
}
