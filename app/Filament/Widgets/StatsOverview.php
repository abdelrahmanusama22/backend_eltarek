<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
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
        $activeVehicles = Vehicle::count();
        $pendingRequests = Booking::where('status', 'pending')->count();

        return [
            Stat::make("Today's Bookings", $todayBookings)
                ->description('Created today')
                ->color('success'),

            Stat::make('Total Customers', number_format($totalCustomers))
                ->description('Registered customers')
                ->color('success'),

            Stat::make('Active Vehicles', $activeVehicles)
                ->description('Catalog records')
                ->color('gray'),

            Stat::make('Pending Requests', $pendingRequests)
                ->description('Requires attention')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),
        ];
    }
}
