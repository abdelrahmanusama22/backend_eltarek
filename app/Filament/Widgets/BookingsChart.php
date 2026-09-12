<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class BookingsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2; // Span 2 of 3 columns

    public function getMaxHeight(): ?string
    {
        return '300px';
    }

    public function getHeading(): ?string
    {
        return 'Booking Activity';
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => '7D',
            '30' => '30D',
            '90' => '90D',
        ];
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 7);
        $confirmed = [];
        $pending = [];
        $labels = [];

        $startDate = Carbon::today()->subDays($days - 1)->startOfDay();
        $counts = Booking::where('created_at', '>=', $startDate)
            ->whereIn('status', ['confirmed', 'pending'])
            ->selectRaw('DATE(created_at) as date_key, status, count(*) as aggregate')
            ->groupBy('date_key', 'status')
            ->get()
            ->groupBy('date_key');

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            if ($days <= 7) {
                $labels[] = $date->format('D');
            } else {
                $labels[] = $date->format('M d');
            }

            $dateStr = $date->toDateString();
            $dayCounts = $counts->get($dateStr, collect())->keyBy('status');

            $c = (int) ($dayCounts->get('confirmed')->aggregate ?? 0);
            $p = (int) ($dayCounts->get('pending')->aggregate ?? 0);

            $confirmed[] = $c;
            $pending[] = $p;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Confirmed',
                    'data' => $confirmed,
                    'borderColor' => '#10B981', // Tailwind Emerald 500
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Pending',
                    'data' => $pending,
                    'borderColor' => '#EF4444', // Tailwind Red 500
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'align' => 'end',
                    'labels' => [
                        'usePointStyle' => true,
                        'boxWidth' => 8,
                        'padding' => 20,
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'grid' => [
                        'color' => 'rgba(255, 255, 255, 0.05)',
                        'drawBorder' => false,
                    ],
                    'border' => ['display' => false],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                    ],
                    'border' => ['display' => false],
                ],
            ],
        ];
    }
}
