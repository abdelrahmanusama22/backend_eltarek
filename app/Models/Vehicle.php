<?php

namespace App\Models;

use App\Support\CatalogEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('active', true)
            ->whereHas('brand', fn (Builder $brand) => $brand->published());
    }

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::flush();
            CatalogEvents::broadcast();
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::flush();
            CatalogEvents::broadcast();
        });
        static::restored(function () {
            \Illuminate\Support\Facades\Cache::flush();
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
     * Resolve image_url to a standardized relative path or external URL.
     * - Already a full URL (http/https) → return as-is
     * - Stored via FileUpload or seeder → return relative /media/... path
     * - Legacy assets path → return relative /assets/... path
     */
    public function getResolvedImageUrlAttribute(): ?string
    {
        $path = $this->image_url;
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

    public function toApi(bool $includeTrims = true): array
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
            'trims' => ($includeTrims && $this->relationLoaded('trims'))
                                        ? $this->trims->map->toApi()->values()->toArray()
                                        : [],
        ];
    }

    public function getStartingPrice(): int
    {
        if ($this->starting_price_egp > 0) {
            return (int) $this->starting_price_egp;
        }

        if ($this->relationLoaded('trims') && $this->trims->count() > 0) {
            return (int) $this->trims->min('price_egp');
        }

        return (int) $this->trims()->min('price_egp');
    }
}
