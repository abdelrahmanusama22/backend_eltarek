<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogImportRun extends Model
{
    protected $fillable = [
        'user_id', 'file_name', 'status', 'processed_rows', 'rejected_rows',
        'new_vehicles', 'new_trims', 'errors', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return ['errors' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }
}
