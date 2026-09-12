<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class TodayOperations extends Widget
{
    protected string $view = 'filament.widgets.today-operations';

    protected int|string|array $columnSpan = 1; // Span 1 of 3

    protected static ?int $sort = 3;
}
