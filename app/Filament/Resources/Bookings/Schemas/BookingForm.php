<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Booking;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')->label('Customer')->relationship('user','email')
                    ->getOptionLabelFromRecordUsing(fn($record)=>"{$record->name} — {$record->email} — {$record->phone}")
                    ->searchable(['name','email','phone'])->preload()->disabled(),
                Select::make('trim_id')
                    ->relationship('trim', 'name')
                    ->disabled(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->disabled(),
                DatePicker::make('date')->disabled(),
                TextInput::make('day_label')
                    ->disabled(),
                TextInput::make('day_label_ar')
                    ->disabled(),
                TextInput::make('time')
                    ->disabled(),
                Select::make('status')
                    ->options(fn (?Booking $record): array => match ($record?->status) {
                        'completed' => ['completed' => 'Completed'],
                        'cancelled' => ['cancelled' => 'Cancelled'],
                        default => [
                            'confirmed' => 'Confirmed',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ],
                    })
                    ->live()
                    ->required()
                    ->default('confirmed'),
                TextInput::make('reference')
                    ->disabled()->dehydrated(false)->helperText('Generated automatically by the booking API.'),
                Textarea::make('cancellation_reason')
                    ->label('Cancellation reason')
                    ->required(fn (Get $get): bool => $get('status') === 'cancelled')
                    ->visible(fn (Get $get): bool => $get('status') === 'cancelled')
                    ->maxLength(1000),
                Textarea::make('admin_notes')
                    ->label('Internal admin notes')
                    ->helperText('Visible to administrators only.')
                    ->maxLength(2000),
            ]);
    }
}
