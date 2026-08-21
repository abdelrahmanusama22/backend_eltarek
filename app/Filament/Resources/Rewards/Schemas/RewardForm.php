<?php

namespace App\Filament\Resources\Rewards\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RewardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('name_ar')
                    ->required(),
                TextInput::make('description')
                    ->required(),
                TextInput::make('description_ar')
                    ->required(),
                TextInput::make('points_cost')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Toggle::make('active')
                    ->required(),
            ]);
    }
}
