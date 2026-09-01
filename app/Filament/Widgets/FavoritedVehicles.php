<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class FavoritedVehicles extends Widget
{
    protected string $view = 'filament.widgets.favorited-vehicles';
    protected int | string | array $columnSpan = 2; // Span 2 of 3
    protected static ?int $sort = 5;
}
