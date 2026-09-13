<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('branches')->whereNull('opening_hours')->orderBy('id')->each(function ($branch): void {
            $open = '09:00';
            $close = '22:00';
            if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(AM|PM)\s*-\s*(\d{1,2})(?::(\d{2}))?\s*(AM|PM)/i', (string) $branch->hours, $m)) {
                $open = date('H:i', strtotime("{$m[1]}:".($m[2] ?: '00')." {$m[3]}"));
                $close = date('H:i', strtotime("{$m[4]}:".($m[5] ?: '00')." {$m[6]}"));
            }
            $schedule = collect(['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'])->map(fn ($day) => [
                'day' => $day, 'open' => $open, 'close' => $close, 'closed' => false,
            ])->all();
            DB::table('branches')->where('id', $branch->id)->update(['opening_hours' => json_encode($schedule)]);
        });
    }

    public function down(): void {}
};
