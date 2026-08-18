<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['active' => 'boolean'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function trims(): HasMany
    {
        return $this->hasMany(Trim::class)->where('active', true)->orderBy('price_egp');
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
            'starting_price_egp' => $this->starting_price_egp,
            'image_url' => $this->image_url,
            'engine_summary' => $this->engine_summary,
            'monthly_from_egp' => $this->monthly_from_egp,
            'badge' => $this->badge,
        ];
    }
}
