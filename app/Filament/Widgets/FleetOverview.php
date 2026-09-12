<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class FleetOverview extends Widget
{
    protected string $view = 'filament.widgets.fleet-overview';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 4;
}
