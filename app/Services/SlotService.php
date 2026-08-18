<?php

namespace App\Services;

use App\Models\AppSetting;
use Carbon\Carbon;

/** Generates upcoming test-drive slots from the configured weekly template. */
class SlotService
{
    private const DAYS_AR = [
        'Sunday' => 'الأحد', 'Monday' => 'الاثنين', 'Tuesday' => 'الثلاثاء',
        'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس',
        'Friday' => 'الجمعة', 'Saturday' => 'السبت',
    ];

    /** @return array<int, array{date:string,day_label:string,day_label_ar:string,times:array}> */
    public static function upcoming(): array
    {
        $config = AppSetting::get('test_drive_times', [
            'default' => ['10:00 AM', '1:00 PM', '4:00 PM'],
            'days_ahead' => 7,
        ]);
        $daysAhead = (int) ($config['days_ahead'] ?? 7);

        $slots = [];
        for ($offset = 0; $offset < $daysAhead; $offset++) {
            $day = Carbon::today()->addDays($offset);
            $key = strtolower($day->format('D')); // sun, mon, fri…
            $times = $config[$key] ?? $config['default'] ?? [];
            if (empty($times)) {
                continue;
            }

            [$label, $labelAr] = match ($offset) {
                0 => ['Today', 'اليوم'],
                1 => ['Tomorrow', 'غداً'],
                default => [$day->format('l'), self::DAYS_AR[$day->format('l')] ?? $day->format('l')],
            };

            $slots[] = [
                'date' => $day->toDateString(),
                'day_label' => $label,
                'day_label_ar' => $labelAr,
                'times' => array_values($times),
            ];
        }

        return $slots;
    }
}
