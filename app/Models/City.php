<?php

namespace App\Models;

use App\Support\CatalogEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'name_ar', 'sort'];

    protected static function booted(): void
    {
        static::saved(fn () => CatalogEvents::broadcast('Cities updated'));
        static::deleted(fn () => CatalogEvents::broadcast('Cities updated'));
    }

    public function toApi(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'name_ar' => $this->name_ar];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
