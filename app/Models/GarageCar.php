<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GarageCar extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'warranty_active'    => 'boolean',
        'vip_service'        => 'boolean',
        'warranty_expires_at' => 'date',
        'next_service_at'    => 'date',
    ];

    public function getResolvedImageUrlAttribute(): ?string
    {
        $path = $this->image_url;
        if (!$path) return null;
        if (str_starts_with($path, 'http')) return $path;
        if (str_starts_with($path, 'assets/')) return url($path);
        return url(Storage::url($path));
    }

    public function toApi(): array
    {
        return [
            'id'                  => $this->id,
            'tracking_code'       => $this->tracking_code,
            'vehicle'             => [
                'name'      => $this->name,
                'image_url' => $this->resolved_image_url,
            ],
            'warranty_status'     => $this->warranty_active ? 'active' : 'expired',
            'warranty_expires_at' => $this->warranty_expires_at?->toDateString(),
            'next_service_in_days' => $this->next_service_at
                ? max(0, (int) now()->startOfDay()->diffInDays($this->next_service_at, false))
                : null,
            'vip_service'         => $this->vip_service,
        ];
    }
}
