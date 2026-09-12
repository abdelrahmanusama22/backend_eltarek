<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GarageServiceRecord extends Model
{
    protected $fillable = ['garage_car_id', 'type', 'serviced_at', 'odometer_km', 'service_center', 'notes'];

    protected $casts = ['serviced_at' => 'date'];

    public function garageCar(): BelongsTo
    {
        return $this->belongsTo(GarageCar::class);
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'serviced_at' => $this->serviced_at?->toDateString(),
            'odometer_km' => $this->odometer_km,
            'service_center' => $this->service_center,
            'notes' => $this->notes,
        ];
    }
}
