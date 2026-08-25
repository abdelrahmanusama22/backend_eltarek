<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles, LogsActivity;

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'is_admin', 'is_active', 'vip_points'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'member_since' => 'date',
            'profile_complete' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Trim::class, 'favorites');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest();
    }

    public function garageCars(): HasMany
    {
        return $this->hasMany(GarageCar::class);
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    // ------------------------------------------------------------- VIP tiers

    /** Ordered tiers with entry thresholds (points). Server-configurable. */
    public static function tiers(): array
    {
        return AppSetting::get('vip_tiers', [
            ['key' => 'silver', 'key_ar' => 'فضي', 'threshold' => 0],
            ['key' => 'gold', 'key_ar' => 'ذهبي', 'threshold' => 15000],
            ['key' => 'platinum', 'key_ar' => 'بلاتيني', 'threshold' => 50000],
        ]);
    }

    public function vipSummary(): array
    {
        $tiers = static::tiers();
        $current = $tiers[0];
        $next = null;
        foreach ($tiers as $tier) {
            if ($this->vip_points >= $tier['threshold']) {
                $current = $tier;
            } elseif ($next === null) {
                $next = $tier;
            }
        }
        $toNext = $next ? max(0, $next['threshold'] - $this->vip_points) : 0;
        $progress = $next && $next['threshold'] > 0
            ? (int) round($this->vip_points / $next['threshold'] * 100)
            : 100;

        return [
            'tier' => $current['key'],
            'tier_ar' => $current['key_ar'],
            'points' => $this->vip_points,
            'member_since' => $this->member_since?->toDateString(),
            'next_tier' => $next['key'] ?? null,
            'points_to_next_tier' => $toNext,
            'progress_percent' => min(100, $progress),
        ];
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'city' => $this->city?->toApi(),
            'age' => $this->age,
            'profile_complete' => $this->profile_complete,
            'vip' => $this->vipSummary(),
        ];
    }
}
