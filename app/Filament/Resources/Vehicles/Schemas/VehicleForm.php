<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Set;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('brand_id')
                    ->relationship('brand', 'name')
                    ->required(),
                TextInput::make('model')->required(),
                TextInput::make('model_ar')->required(),
                TextInput::make('year')->required()->numeric(),
                Select::make('category')
                    ->options([
                        'SUV' => 'SUV',
                        'Sedan' => 'Sedan',
                        'Electric' => 'Electric',
                        'Coupe' => 'Coupe',
                    ])
                    ->required(),
                TextInput::make('starting_price_egp')
                    ->required()
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, Set $set) {
                        if (empty($state) || $state <= 0) {
                            $set('active', false);
                        }
                    }),
                FileUpload::make('image_url')
                    ->label('Vehicle Image')
                    ->image()
                    ->disk('public')
                    ->directory('vehicles')
                    ->imagePreviewHeight('200')
                    ->required(),
                TextInput::make('engine_summary')->required()->default(''),
                TextInput::make('monthly_from_egp')->numeric()->default(null),
                Select::make('badge')
                    ->options([
                        'New' => 'New',
                        'Hot' => 'Hot',
                        'Luxury' => 'Luxury',
                        'Sale' => 'Sale',
                    ])
                    ->default(null),
                TextInput::make('sort')->required()->numeric()->default(0),
                Toggle::make('active')->required(),
            ]);
    }
}
