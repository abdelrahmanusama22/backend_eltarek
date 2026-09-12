<?php

namespace App\Models;

use App\Support\CatalogEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Vehicle extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'brand_id', 'model', 'model_ar', 'year', 'category', 'starting_price_egp',
        'image_url', 'engine_summary', 'monthly_from_egp', 'badge', 'sort', 'active',
    ];

    protected $casts = ['active' => 'boolean'];

    protected static function booted()
    {
        static::saved(function () {
            CatalogEvents::broadcast();
        });
        static::deleted(function () {
            CatalogEvents::broadcast();
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function trims(): HasMany
    {
        return $this->hasMany(Trim::class)->where('active', true)->where('price_egp', '>', 0)->orderBy('price_egp');
    }

    /**
     * Resolve image_url to a full public URL regardless of how it was stored.
     * - Already a full URL → return as-is
     * - Stored via FileUpload (e.g. "vehicles/abc.jpg") → Storage::url()
     * - Legacy assets path → prefix with APP_URL
     */
    public function getResolvedImageUrlAttribute(): ?string
    {
        $path = $this->image_url;
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        if (str_starts_with($path, 'assets/')) {
            return url($path);
        }

        return url('/media/'.$path);
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'model' => $this->model,
            'model_ar' => $this->model_ar,
            'year' => $this->year,
            'category' => $this->category,
            'starting_price_egp' => $this->getStartingPrice(),
            'image_url' => $this->resolved_image_url,
            'engine_summary' => $this->engine_summary,
            'monthly_from_egp' => $this->monthly_from_egp,
            'badge' => $this->badge,
            'trims' => $this->relationLoaded('trims')
                                        ? $this->trims->map->toApi()->values()->toArray()
                                        : [],
        ];
    }

    public function getStartingPrice(): int
    {
        if ($this->relationLoaded('trims') && $this->trims->count() > 0) {
            return (int) $this->trims->min('executive_price');
        }

        $min = $this->trims()
            ->get()
            ->min('executive_price');

        return (int) ($min ?? $this->starting_price_egp);
    }
}
