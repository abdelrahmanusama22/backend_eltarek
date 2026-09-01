<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.custom-dashboard';

    public static function getNavigationUrl(): string
    {
        return '#';
    }

    public static function getNavigationBadge(): ?string
    {
        return 'قريباً';
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }
}
