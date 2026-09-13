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
        $minimumNotice = max(0, (int) ($config['min_notice_minutes'] ?? 60));
        $earliestAllowed = Carbon::now()->addMinutes($minimumNotice);

        $blockedDates = AppSetting::get('blocked_dates', []);
        $blockedDatesArray = array_column($blockedDates, 'date');

        $slots = [];
        for ($offset = 0; $offset < $daysAhead; $offset++) {
            $day = Carbon::today()->addDays($offset);

            if (in_array($day->toDateString(), $blockedDatesArray)) {
                continue; // Skip holiday/blocked date
            }

            $key = strtolower($day->format('D')); // sun, mon, fri…
            // An explicitly configured empty day is closed; default is only for legacy configs.
            $times = array_key_exists($key, $config)
                ? (array) $config[$key]
                : (array) ($config['default'] ?? []);
            $times = collect($times)
                ->filter(function (mixed $time) use ($day, $earliestAllowed): bool {
                    try {
                        $slot = Carbon::createFromFormat(
                            'Y-m-d g:i A',
                            $day->toDateString().' '.strtoupper(trim((string) $time))
                        );
                        return $slot->greaterThanOrEqualTo($earliestAllowed);
                    } catch (\Throwable) {
                        return false;
                    }
                })
                ->values()
                ->all();
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
