<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['active' => 'boolean'];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class)->where('active', true)->orderBy('sort');
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
            'logo_url' => $this->logo_url,
        ];
    }
}
