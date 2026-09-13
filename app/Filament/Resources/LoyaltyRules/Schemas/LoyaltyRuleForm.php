<?php

namespace App\Filament\Resources\LoyaltyRules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LoyaltyRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Rule Name')
                    ->required()
                    ->maxLength(255),
                Select::make('action')
                    ->label('Trigger Action')
                    ->options([
                        'test_drive' => 'Test Drive Completed',
                        'register' => 'New User Registration',
                    ])
                    ->required(),
                TextInput::make('points_awarded')
                    ->label('Points to Award')
                    ->numeric()
                    ->required()
                    ->default(100),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
