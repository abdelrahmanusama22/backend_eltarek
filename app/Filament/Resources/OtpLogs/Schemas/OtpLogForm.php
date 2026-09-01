<?php

namespace App\Filament\Resources\OtpLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OtpLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('otp')
                    ->required(),
                DateTimePicker::make('expires_at'),
                Toggle::make('is_used')
                    ->required(),
            ]);
    }
}
