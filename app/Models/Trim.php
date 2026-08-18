<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trim extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'highlights' => 'array',
        'specs' => 'array',
        'metrics' => 'array',
        'gallery' => 'array',
        'is_most_popular' => 'boolean',
        'has_360_view' => 'boolean',
        'in_test_drive_fleet' => 'boolean',
        'active' => 'boolean',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'price_egp' => $this->price_egp,
            'original_price_egp' => $this->original_price_egp,
            'is_most_popular' => $this->is_most_popular,
            'subtitle' => $this->subtitle,
            'has_360_view' => $this->has_360_view,
            'view_360_url' => $this->view_360_url,
            'suggested_comparison_trim_id' => $this->suggested_comparison_trim_id,
            'highlights' => $this->highlights,
            'specs' => $this->specs,
            'metrics' => $this->metrics,
            'gallery' => $this->gallery,
        ];
    }
}
