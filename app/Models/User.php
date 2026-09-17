<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use RuntimeException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'google_id',
        'phone',
        'password',
        'age',
        'city_id',
        'avatar_url',
        'profile_complete',
        'is_active',
        'is_admin',
        'points',
        'member_since',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'is_admin', 'is_active', 'points'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'member_since' => 'date',
            'profile_complete' => 'boolean',
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
            'points' => 'integer',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin && (bool) $this->is_active;
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

    public function garageLinkRequests(): HasMany
    {
        return $this->hasMany(GarageLinkRequest::class);
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class)->latest();
    }

    public function addPoints(int $points, ?string $description = null, string $type = 'credit'): void
    {
        $amount = abs($points);
        DB::transaction(function () use ($amount, $description, $type): void {
            $user = self::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            if ($type === 'debit' && $user->points < $amount) {
                throw new RuntimeException('Insufficient points balance.');
            }

            $txAmount = $type === 'debit' ? -$amount : $amount;
            if ($type === 'debit') {
                $user->decrement('points', $amount);
            } else {
                $user->increment('points', $amount);
            }
            $user->pointTransactions()->create([
                'points' => $txAmount,
                'description' => $description,
                'type' => $type,
            ]);
            $this->points = $user->fresh()->points;
        }, 3);
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
            ['key' => 'gold', 'key_ar' => 'ذهبي', 'threshold' => 5000],
            ['key' => 'platinum', 'key_ar' => 'بلاتيني', 'threshold' => 15000],
        ]);
    }

    public function vipSummary(): array
    {
        $tiers = self::tiers();

        $current = $tiers[0];
        $next = null;
        foreach ($tiers as $index => $tier) {
            if ($this->points >= $tier['threshold']) {
                $current = $tier;
                $next = $tiers[$index + 1] ?? null;
            } else {
                break;
            }
        }

        $toNext = $next ? max(0, $next['threshold'] - $this->points) : 0;
        $currentFloor = $current['threshold'];
        $nextCeil = $next['threshold'] ?? $currentFloor;
        $tierSpan = $nextCeil - $currentFloor;
        $progress = ($next && $tierSpan > 0)
            ? (int) round(($this->points - $currentFloor) / $tierSpan * 100)
            : 100;

        return [
            'tier' => $current['key'],
            'tier_ar' => $current['key_ar'],
            'points' => (int) $this->points,
            'member_since' => $this->member_since?->toDateString(),
            'next_tier' => $next['key'] ?? null,
            'points_to_next_tier' => $toNext,
            'progress_percent' => min(100, max(0, $progress)),
        ];
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => is_string($this->avatar_url) && str_starts_with($this->avatar_url, 'avatars/')
                ? '/storage/'.$this->avatar_url
                : $this->avatar_url,
            'city' => $this->city?->toApi(),
            'age' => $this->age,
            'profile_complete' => $this->profile_complete,
            'vip' => $this->vipSummary(),
        ];
    }
}
