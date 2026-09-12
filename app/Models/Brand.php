<?php

namespace App\Models;

use App\Support\CatalogEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = ['name', 'name_ar', 'tagline', 'tagline_ar', 'monogram', 'tier', 'logo_url', 'sort', 'active'];

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

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class)->where('active', true)->where('starting_price_egp', '>', 0)->orderBy('sort');
    }

    public function getResolvedLogoUrlAttribute(): ?string
    {
        $path = $this->logo_url;
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
