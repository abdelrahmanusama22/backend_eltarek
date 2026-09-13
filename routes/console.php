<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Schedule::command('catalog:sync')->dailyAt('02:00');
Schedule::call(function (): void {
    AnalyticsEvent::where('created_at', '<', now()->subDays((int) env('ANALYTICS_RETENTION_DAYS', 90)))->delete();
})->dailyAt('03:00')->name('analytics-retention')->withoutOverlapping();
Schedule::call(fn () => Cache::forever('health:scheduler_heartbeat', now()->timestamp))
    ->name('scheduler-heartbeat')
    ->everyMinute();
