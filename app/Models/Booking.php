<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $guarded = [];

    protected $casts = ['date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trim(): BelongsTo
    {
        return $this->belongsTo(Trim::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function toApi(): array
    {
        $vehicle = $this->trim?->vehicle;

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'type' => 'test_drive',
            'trim' => [
                'trim_id' => $this->trim_id,
                'name' => $vehicle ? trim($vehicle->model.' '.$this->trim->name) : '',
                'name_ar' => $vehicle ? trim($vehicle->model_ar.' '.$this->trim->name_ar) : '',
                'image_url' => $vehicle?->resolved_image_url ?? '',
            ],
            'branch' => [
                'id' => $this->branch_id,
                'name' => $this->branch->name ?? '',
                'name_ar' => $this->branch->name_ar ?? '',
            ],
            'date' => $this->date?->toDateString(),
            'day_label' => $this->day_label,
            'day_label_ar' => $this->day_label_ar,
            'time' => $this->time,
        ];
    }
}
