<?php

namespace App\Filament\Resources\UserNotifications\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('type')
                    ->required()
                    ->default('general'),
                TextInput::make('title')
                    ->required(),
                TextInput::make('title_ar')
                    ->required()
                    ->default(''),
                TextInput::make('body')
                    ->required()
                    ->default(''),
                TextInput::make('body_ar')
                    ->required()
                    ->default(''),
                Toggle::make('is_read')
                    ->required(),
            ]);
    }
}
