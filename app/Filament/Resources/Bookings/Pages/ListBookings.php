<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

class ListBookings extends Page
{
    use WithPagination;

    protected static string $resource = BookingResource::class;

    protected string $view = 'filament.pages.custom-list-bookings';

    public $activeTab = 'all';
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function bookingsQuery(): Builder
    {
        $query = Booking::query()->with(['user', 'trim', 'branch'])->latest();

        if ($this->activeTab === 'pending') {
            $query->where('status', 'pending');
        } elseif ($this->activeTab === 'confirmed') {
            $query->where('status', 'confirmed');
        } elseif ($this->activeTab === 'completed') {
            $query->where('status', 'completed');
        }

        if (trim($this->search) !== '') {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($this->search)).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('reference', 'like', $term)
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $term)->orWhere('phone', 'like', $term))
                    ->orWhereHas('trim', fn (Builder $t) => $t->where('name', 'like', $term));
            });
        }

        return $query;
    }

    public function getBookingsProperty()
    {
        return $this->bookingsQuery()->paginate(20);
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['all', 'pending', 'confirmed', 'completed'], true)) {
            $this->activeTab = $tab;
            $this->resetPage();
        }
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }
}
