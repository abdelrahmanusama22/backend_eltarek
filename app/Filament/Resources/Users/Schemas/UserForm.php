<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->default(null),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->default(null),
                Toggle::make('is_admin')
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->default(null),
                TextInput::make('age')
                    ->numeric()
                    ->default(null),
                Select::make('city_id')
                    ->relationship('city', 'name')
                    ->default(null),
                TextInput::make('vip_tier')
                    ->required()
                    ->default('silver'),
                TextInput::make('vip_points')
                    ->required()
                    ->numeric()
                    ->default(0),
                DatePicker::make('member_since'),
                Toggle::make('profile_complete')
                    ->required(),
                TextInput::make('avatar_url')
                    ->url()
                    ->default(null),
            ]);
    }
}
