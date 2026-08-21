<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandResource;
use Filament\Resources\Pages\Page;
use App\Models\Brand;

class ListBrands extends Page
{
    protected static string $resource = BrandResource::class;

    protected string $view = 'filament.pages.custom-list-brands';

    public function getBrandsProperty()
    {
        return Brand::all();
    }
    
    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }
}
