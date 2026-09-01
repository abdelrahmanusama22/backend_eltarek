<?php

namespace App\Filament\Resources\Redemptions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RedemptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('reward_id')
                    ->required()
                    ->numeric(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('points_spent')
                    ->required()
                    ->numeric(),
                DatePicker::make('valid_until')
                    ->required(),
            ]);
    }
}
