<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\GarageLinkRequest;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $todayBookings = Booking::whereDate('created_at', Carbon::today())->count();
        $totalCustomers = User::where('is_admin', false)->count();
        $activeVehicles = Vehicle::where('active', true)->count();
        $pendingGarageLinks = GarageLinkRequest::where('status', 'pending')->count();

        return [
            Stat::make("Today's Bookings", $todayBookings)
                ->description('Created today')
                ->color('success'),

            Stat::make('Total Customers', number_format($totalCustomers))
                ->description('Registered customers')
                ->color('success'),

            Stat::make('Active Vehicles', $activeVehicles)
                ->description('Published catalog vehicles')
                ->color('gray'),

            Stat::make('Pending Garage Link Requests', $pendingGarageLinks)
                ->description('Awaiting admin approval')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),
        ];
    }
}
