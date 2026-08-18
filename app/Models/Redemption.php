<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redemption extends Model
{
    protected $guarded = [];

    protected $casts = ['valid_until' => 'date:Y-m-d'];
}
