<?php

namespace App\Filament\Pages;

use App\Models\AnalyticsEvent;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Trim;
use Carbon\Carbon;
use Filament\Pages\Page;
use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class AnalyticsPage extends Page
{
    protected string $view = 'filament.pages.analytics-page';

    public static function getNavigationGroup(): ?string
    {
        return 'Settings & Analytics';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-chart-bar-square';
    }

    public static function getNavigationSort(): ?int
    {
        return 90;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Analytics & Mobile Insights';
    }

    public string $period = '7days';

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    public function getStartDate(): Carbon
    {
        return match ($this->period) {
            'today'   => Carbon::today(),
            '30days'  => Carbon::now()->subDays(30)->startOfDay(),
            'all'     => Carbon::createFromTimestamp(0),
            default   => Carbon::now()->subDays(7)->startOfDay(),
        };
    }

    public function getMetrics(): array
    {
        $startDate = $this->getStartDate();

        $totalBookings = Booking::where('created_at', '>=', $startDate)->count();
        $confirmedBookings = Booking::where('created_at', '>=', $startDate)->where('status', 'confirmed')->count();
        $conversionRate = $totalBookings > 0 ? round(($confirmedBookings / $totalBookings) * 100, 1) : 0;

        $totalEvents = AnalyticsEvent::where('created_at', '>=', $startDate)->count();
        $activeUsers = User::where('created_at', '>=', $startDate)->count();
        $totalCustomers = User::where('is_admin', false)->count();

        // Top viewed vehicles from AnalyticsEvent
        $topViewedVehicles = AnalyticsEvent::where('event_name', 'vehicle_viewed')
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.model')) as model_name"), DB::raw('count(*) as views'))
            ->groupBy('model_name')
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        // Branch distribution
        $branchBookings = Booking::where('created_at', '>=', $startDate)
            ->with('branch')
            ->select('branch_id', DB::raw('count(*) as total'))
            ->groupBy('branch_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Recent live events
        $recentEvents = AnalyticsEvent::with('user')
            ->latest('created_at')
            ->limit(12)
            ->get();

        // Chart Data (Day by Day)
        $daysCount = match ($this->period) {
            'today'  => 1,
            '30days' => 30,
            'all'    => 30,
            default  => 7,
        };

        $chartLabels = [];
        $chartBookings = [];
        $chartEvents = [];

        for ($i = $daysCount - 1; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $chartLabels[] = $d->format('M d');
            $chartBookings[] = Booking::whereDate('created_at', $d)->count();
            $chartEvents[] = AnalyticsEvent::whereDate('created_at', $d)->count();
        }

        return [
            'totalBookings'     => $totalBookings,
            'confirmedBookings' => $confirmedBookings,
            'conversionRate'    => $conversionRate,
            'totalEvents'       => $totalEvents,
            'activeUsers'       => $activeUsers,
            'totalCustomers'    => $totalCustomers,
            'topViewedVehicles' => $topViewedVehicles,
            'branchBookings'    => $branchBookings,
            'recentEvents'      => $recentEvents,
            'chartLabels'       => $chartLabels,
            'chartBookings'     => $chartBookings,
            'chartEvents'       => $chartEvents,
        ];
    }
}
