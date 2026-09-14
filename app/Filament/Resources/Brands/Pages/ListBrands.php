<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandResource;
use App\Models\Brand;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

class ListBrands extends Page
{
    use WithPagination;

    protected static string $resource = BrandResource::class;

    protected string $view = 'filament.pages.custom-list-brands';

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function brandsQuery(): Builder
    {
        return Brand::query()->withCount('vehicles')
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($this->search)).'%';
                $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('name_ar', 'like', $term));
            })
            ->orderBy('id');
    }

    public function getBrandsProperty()
    {
        return $this->brandsQuery()->paginate(20);
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }
}
