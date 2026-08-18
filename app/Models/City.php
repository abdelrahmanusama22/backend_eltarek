<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function toApi(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'name_ar' => $this->name_ar];
    }
}
