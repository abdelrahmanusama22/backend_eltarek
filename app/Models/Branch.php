<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'services' => 'array',
        'is_open' => 'boolean',
        'active' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function toApi(?float $lat = null, ?float $lng = null): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'address' => $this->address,
            'address_ar' => $this->address_ar,
            'phone' => $this->phone,
            'hours' => $this->hours,
            'hours_ar' => $this->hours_ar,
            'is_open' => $this->is_open,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'services' => $this->services ?? [],
        ];
        if ($lat !== null && $lng !== null) {
            $data['distance_km'] = round($this->distanceKm($lat, $lng), 1);
        }

        return $data;
    }

    /** Haversine distance in km. */
    public function distanceKm(float $lat, float $lng): float
    {
        $r = 6371.0;
        $dLat = deg2rad($this->lat - $lat);
        $dLng = deg2rad($this->lng - $lng);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat)) * cos(deg2rad((float) $this->lat)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }
}
