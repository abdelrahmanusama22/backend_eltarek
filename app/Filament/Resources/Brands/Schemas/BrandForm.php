<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('name_ar')->required()->maxLength(255),
                TextInput::make('tagline')->required()->maxLength(255)->default(''),
                TextInput::make('tagline_ar')->required()->maxLength(255)->default(''),
                TextInput::make('monogram')->required()->maxLength(5)->default('?'),
                TextInput::make('tier')->required()->maxLength(50)->default('standard'),
                FileUpload::make('logo_url')
                    ->label('Brand Logo')
                    ->image()
                    ->disk('public')
                    ->directory('brands')
                    ->imagePreviewHeight('150')
                    ->default(null),
                TextInput::make('sort')->required()->numeric()->default(0),
                Toggle::make('active')->required(),
            ]);
    }
}
