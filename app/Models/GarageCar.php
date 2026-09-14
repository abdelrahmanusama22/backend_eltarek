<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class GarageCar extends Model
{
    protected $fillable = [
        'user_id', 'vehicle_id', 'trim_id', 'tracking_code', 'name', 'image_url', 'warranty_active',
        'warranty_expires_at', 'next_service_at', 'vip_service',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function trim(): BelongsTo { return $this->belongsTo(Trim::class); }
    public function serviceRecords(): HasMany { return $this->hasMany(GarageServiceRecord::class)->latest('serviced_at'); }

    public function getIsWarrantyActiveAttribute(): bool
    {
        return $this->warranty_active && (! $this->warranty_expires_at || $this->warranty_expires_at->isToday() || $this->warranty_expires_at->isFuture());
    }

    protected $casts = [
        'warranty_active' => 'boolean',
        'vip_service' => 'boolean',
        'warranty_expires_at' => 'date',
        'next_service_at' => 'date',
    ];

    public function getResolvedImageUrlAttribute(): ?string
    {
        $path = $this->image_url;
        if (! $path) {
            return $this->vehicle?->resolved_image_url;
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
            'tracking_code' => $this->tracking_code,
            'vehicle' => [
                'name' => $this->name ?: $this->vehicle?->model,
                'image_url' => $this->resolved_image_url,
            ],
            'vehicle_id' => $this->vehicle_id,
            'trim_id' => $this->trim_id,
            'warranty_status' => $this->is_warranty_active ? 'active' : 'expired',
            'warranty_expires_at' => $this->warranty_expires_at?->toDateString(),
            'next_service_at' => $this->next_service_at?->toDateString(),
            'next_service_in_days' => $this->next_service_at
                ? max(0, (int) now()->startOfDay()->diffInDays($this->next_service_at, false))
                : null,
            'vip_service' => $this->vip_service,
            'service_history' => $this->relationLoaded('serviceRecords')
                ? $this->serviceRecords->map->toApi()->values()->all()
                : [],
            'service_history_total' => $this->service_records_count ?? 0,
        ];
    }
}
