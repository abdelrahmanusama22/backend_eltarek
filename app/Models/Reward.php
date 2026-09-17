<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    protected $fillable = ['name', 'name_ar', 'description', 'description_ar', 'points_cost', 'stock', 'per_user_limit', 'validity_days', 'active'];

    protected $casts = ['active' => 'boolean', 'stock' => 'integer', 'per_user_limit' => 'integer', 'validity_days' => 'integer'];

    public function redemptions(): HasMany { return $this->hasMany(Redemption::class); }

    public function toApi(?User $user = null): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'points_cost' => $this->points_cost,
            'user_can_redeem' => $user !== null && $user->points >= $this->points_cost,
            'in_stock' => $this->stock === null || $this->stock > 0,
            'remaining_for_user' => $user && $this->per_user_limit !== null ? max(0, $this->per_user_limit - ($this->user_redemptions_count ?? $this->redemptions()->where('user_id', $user->id)->count())) : null,
        ];
    }
}
