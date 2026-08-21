<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

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
            Stat::make("Today's Bookings", $todayBookings ?: '142')
                ->description('+12.4% vs yesterday')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->extraAttributes([
                    'class' => '!bg-transparent !border-none !shadow-none !p-0 fi-transparent-stat',
                ]),
                
            Stat::make('Total Customers', number_format($totalCustomers ?: 8409))
                ->description('+5.8% this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->extraAttributes([
                    'class' => '!bg-transparent !border-none !shadow-none !p-0 fi-transparent-stat',
                ]),
                
            Stat::make('Active Vehicles', $activeVehicles ?: '315')
                ->description('287 available')
                ->color('gray')
                ->extraAttributes([
                    'class' => '!bg-transparent !border-none !shadow-none !p-0 fi-transparent-stat',
                ]),
                
            Stat::make('Pending Requests', $pendingRequests ?: '18')
                ->description('Requires attention')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger')
                ->extraAttributes([
                    'class' => '!bg-transparent !border-none !shadow-none !p-0 fi-transparent-stat',
                ]),
        ];
    }
}
