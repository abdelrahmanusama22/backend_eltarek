<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['active' => 'boolean'];

    public function toApi(?User $user = null): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'points_cost' => $this->points_cost,
            'user_can_redeem' => $user !== null && $user->vip_points >= $this->points_cost,
        ];
    }
}
