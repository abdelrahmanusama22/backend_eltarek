<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Select::make('trim_id')
                    ->relationship('trim', 'name')
                    ->required(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required(),
                DatePicker::make('date'),
                TextInput::make('day_label')
                    ->required()
                    ->default(''),
                TextInput::make('day_label_ar')
                    ->required()
                    ->default(''),
                TextInput::make('time')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('confirmed'),
                TextInput::make('reference')
                    ->required(),
            ]);
    }
}
