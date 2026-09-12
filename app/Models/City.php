<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'name_ar', 'sort'];

    public function toApi(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'name_ar' => $this->name_ar];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
