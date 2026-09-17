<?php

namespace App\Models;

use App\Support\CatalogEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = ['name', 'name_ar', 'tagline', 'tagline_ar', 'monogram', 'tier', 'logo_url', 'sort', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    protected static function booted()
    {
        static::saved(function () {
            CatalogEvents::broadcast();
        });
        static::deleted(function () {
            CatalogEvents::broadcast();
        });
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class)->where('active', true)->where('starting_price_egp', '>', 0)->orderBy('sort');
    }

    public function getMonogramAttribute(?string $value): string
    {
        if (! empty($value) && $value !== '?') {
            return $value;
        }
        $name = trim((string) $this->name);

        return ! empty($name) ? strtoupper(substr($name, 0, min(2, strlen($name)))) : 'ET';
    }

    public function getResolvedLogoUrlAttribute(): ?string
    {
        $path = $this->logo_url;
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

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'tagline' => $this->tagline,
            'tagline_ar' => $this->tagline_ar,
            'monogram' => $this->monogram,
            'tier' => $this->tier,
            'logo_url' => $this->resolved_logo_url,
        ];
    }
}
