<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\Page;
use App\Models\Booking;

class ListBookings extends Page
{
    protected static string $resource = BookingResource::class;

    protected string $view = 'filament.pages.custom-list-bookings';

    public $activeTab = 'all';

    public function getBookingsProperty()
    {
        $query = Booking::query()->latest();

        if ($this->activeTab === 'pending') {
            $query->where('status', 'pending');
        } elseif ($this->activeTab === 'confirmed') {
            $query->where('status', 'confirmed');
        } elseif ($this->activeTab === 'completed') {
            $query->where('status', 'completed');
        }

        return $query->get();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }
}
