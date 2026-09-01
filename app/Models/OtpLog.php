<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpLog extends Model
{
    protected $guarded = [];
    protected $casts = ['expires_at' => 'datetime', 'is_used' => 'boolean'];

    //
}
