<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyRule extends Model
{
    protected $fillable = ['name', 'action', 'points_awarded', 'is_active'];
}
