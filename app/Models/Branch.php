<?php

namespace App\Models;

use App\Support\CatalogEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'city_id', 'image', 'name', 'name_ar', 'address', 'address_ar', 'phone',
        'whatsapp', 'hours', 'hours_ar', 'opening_hours', 'timezone', 'is_open', 'lat', 'lng', 'services', 'active',
    ];

    protected $casts = [
        'services' => 'array',
        'opening_hours' => 'array',
        'is_open' => 'boolean',
        'active' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => CatalogEvents::broadcast('Branches updated'));
        static::deleted(fn () => CatalogEvents::broadcast('Branches updated'));
        static::restored(fn () => CatalogEvents::broadcast('Branches updated'));
    }

    public function getResolvedImageUrlAttribute(): ?string
    {
        $path = $this->image;
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $clean = ltrim((string) $path, '/');
        if (str_starts_with($clean, 'storage/')) {
            $clean = substr($clean, 8);
        }
        if (str_starts_with($clean, 'media/')) {
            $clean = substr($clean, 6);
        }
        if (str_starts_with($clean, 'assets/')) {
            return '/'.$clean;
        }

        return '/media/'.$clean;
    }

    public function toApi(?float $lat = null, ?float $lng = null): array
    {
        $data = [
            'id' => $this->id,
            'city_id' => $this->city_id,
            'image' => $this->resolved_image_url,
            'image_url' => $this->resolved_image_url,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'address' => $this->address,
            'address_ar' => $this->address_ar,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp ?: $this->phone,
            'hours' => $this->hours,
            'hours_ar' => $this->hours_ar,
            'is_open' => $this->isOpenNow(),
            'lat' => $this->lat,
            'lng' => $this->lng,
            'services' => $this->services ?? [],
        ];
        if ($lat !== null && $lng !== null) {
            $data['distance_km'] = round($this->distanceKm($lat, $lng), 1);
        }

        return $data;
    }

    public function isOpenNow(): bool
    {
        if (empty($this->opening_hours)) return (bool) $this->is_open;
        $now = now($this->timezone ?: 'Africa/Cairo');
        $day = strtolower($now->format('D'));
        foreach ($this->opening_hours as $slot) {
            if (($slot['day'] ?? null) !== $day || ($slot['closed'] ?? false)) continue;
            $open = $now->copy()->setTimeFromTimeString($slot['open'] ?? '00:00');
            $close = $now->copy()->setTimeFromTimeString($slot['close'] ?? '00:00');
            if ($close->lessThanOrEqualTo($open)) $close->addDay();
            if ($now->betweenIncluded($open, $close)) return true;
        }
        return false;
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

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
