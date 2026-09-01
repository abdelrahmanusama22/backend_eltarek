<?php

namespace App\Filament\Resources\GarageCars\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GarageCarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->label('User ID')
                    ->required()
                    ->numeric(),
                TextInput::make('tracking_code')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Car Name')
                    ->required(),
                FileUpload::make('image_url')
                    ->label('Car Image')
                    ->image()
                    ->disk('public')
                    ->directory('garage')
                    ->imagePreviewHeight('150'),
                Toggle::make('warranty_active')->required(),
                DatePicker::make('warranty_expires_at'),
                DatePicker::make('next_service_at'),
                Toggle::make('vip_service')->required(),
            ]);
    }
}
