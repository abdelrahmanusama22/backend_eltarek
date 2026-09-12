<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand Information (بيانات الماركة)')
                    ->schema([
                        TextInput::make('name')
                            ->label('Brand Name (English)')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, $state, $get) {
                                if (empty($get('monogram')) && ! empty($state)) {
                                    $set('monogram', strtoupper(substr(trim($state), 0, 2)));
                                }
                            }),
                        TextInput::make('name_ar')
                            ->label('Brand Name (Arabic)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('monogram')
                            ->label('Monogram / Abbreviation (رمز الماركة)')
                            ->maxLength(5)
                            ->helperText('Short symbol shown when logo is absent (e.g. BMW, TY, MB, OOOO).')
                            ->default(null),
                        Select::make('tier')
                            ->label('Brand Tier (التصنيف)')
                            ->options([
                                'standard' => 'Standard (اقتصادي / متوسط)',
                                'premium' => 'Premium (فاخر)',
                            ])
                            ->default('standard')
                            ->required(),
                        TextInput::make('tagline')
                            ->label('Tagline (English)')
                            ->maxLength(255)
                            ->default(''),
                        TextInput::make('tagline_ar')
                            ->label('Tagline (Arabic)')
                            ->maxLength(255)
                            ->default(''),
                        TextInput::make('sort')
                            ->label('Sort Order (الترتيب)')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('active')
                            ->label('Active (نشط)')
                            ->default(true),
                    ])->columns(2),

                Section::make('Brand Logo (شعار الماركة)')
                    ->schema([
                        FileUpload::make('logo_url')
                            ->label('Brand Logo (شعار أو لوجو الماركة)')
                            ->image()
                            ->disk('public')
                            ->directory('brands')
                            ->imagePreviewHeight('150')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
