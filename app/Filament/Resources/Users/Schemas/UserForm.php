<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                Toggle::make('is_admin')
                    ->label('Is Admin?')
                    ->live()
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(fn ($get): bool => $get('is_admin') === true)
                    ->visible(fn ($get): bool => $get('is_admin') === true),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation, $get): bool => $operation === 'create' && $get('is_admin') === true)
                    ->visible(fn ($get): bool => $get('is_admin') === true)
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->maxLength(255),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->visible(fn ($get): bool => $get('is_admin') === true),
            ]);
    }
}
