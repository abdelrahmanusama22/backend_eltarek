<?php

namespace App\Models;

use App\Domain\Pricing\PricingService;
use App\Support\CatalogEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Trim extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'vehicle_id', 'legacy_car_id', 'name', 'name_ar', 'price_egp',
        'original_price_egp', 'total_price', 'booking_deposit', 'zero_interest_price',
        'price_9pct', 'markup_percentage', 'is_on_hold', 'colors', 'financing_notes',
        'is_most_popular', 'subtitle', 'has_360_view', 'view_360_url',
        'suggested_comparison_trim_id', 'highlights', 'specs', 'metrics', 'gallery',
        'in_test_drive_fleet', 'fleet_sort', 'active',
    ];

    protected $casts = [
        'highlights' => 'array',
        'specs' => 'array',
        'metrics' => 'array',
        'gallery' => 'array',
        'is_most_popular' => 'boolean',
        'has_360_view' => 'boolean',
        'in_test_drive_fleet' => 'boolean',
        'active' => 'boolean',
        'is_on_hold' => 'boolean',
        'markup_percentage' => 'float',
    ];

    protected static function booted()
    {
        static::saving(function (Trim $trim) {
            if ($trim->price_egp <= 0) {
                $trim->active = false;
            }
        });

        $updateVehiclePrice = function (Trim $trim) {
            if ($trim->vehicle_id) {
                $vehicle = Vehicle::find($trim->vehicle_id);
                if ($vehicle) {
                    $min = Trim::where('vehicle_id', $vehicle->id)
                        ->where('active', true)
                        ->min(DB::raw('price_egp * (1 + COALESCE(markup_percentage, 5) / 100)'));
                    $vehicle->updateQuietly(['starting_price_egp' => (int) ($min ?? 0)]);
                }
            }
        };

        static::saved(function (Trim $trim) use ($updateVehiclePrice) {
            $updateVehiclePrice($trim);
            CatalogEvents::broadcast();
        });
        static::deleted(function (Trim $trim) use ($updateVehiclePrice) {
            $updateVehiclePrice($trim);
            CatalogEvents::broadcast();
        });
        static::restored(function (Trim $trim) use ($updateVehiclePrice) {
            $updateVehiclePrice($trim);
            CatalogEvents::broadcast();
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function getResolvedGalleryAttribute(): array
    {
        $gallery = $this->gallery ?? [];

        return array_values(array_map(function ($path) {
            if (empty($path)) {
                return '';
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
        }, array_filter((array) $gallery)));
    }

    /**
     * Computed executive price = official price × (1 + markup_percentage / 100)
     * The markup_percentage is editable per trim from the dashboard.
     */
    public function getExecutivePriceAttribute(): int
    {
        return PricingService::executivePrice($this)->egp;
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'price_egp' => $this->executive_price,
            'official_price_egp' => $this->price_egp,
            'original_price_egp' => $this->original_price_egp,
            'is_most_popular' => $this->is_most_popular,
            'subtitle' => $this->subtitle,
            'has_360_view' => $this->has_360_view,
            'view_360_url' => $this->view_360_url,
            'suggested_comparison_trim_id' => $this->suggested_comparison_trim_id,
            'highlights' => $this->highlights,
            'specs' => $this->specs,
            'metrics' => $this->metrics,
            'gallery' => $this->resolved_gallery,
            // Pricing details from import
            'markup_percentage' => $this->markup_percentage ?? 5.0,
            'executive_price' => $this->executive_price,
            'total_price' => $this->total_price,
            'booking_deposit' => $this->booking_deposit,
            'zero_interest_price' => $this->zero_interest_price,
            'price_9pct' => $this->price_9pct,
            // Availability
            'is_on_hold' => $this->is_on_hold,
            'colors' => $this->colors,
            'financing_notes' => $this->financing_notes,
        ];
    }
}
