<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Dotswan\MapPicker\Fields\Map;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                TextInput::make('name_ar')->required(),
                TextInput::make('address')->required(),
                TextInput::make('address_ar')->required(),
                TextInput::make('phone')->tel()->required(),
                TextInput::make('hours')->required(),
                TextInput::make('hours_ar')->required(),
                Toggle::make('is_open')->required(),
                Toggle::make('active')->required(),
                Textarea::make('services')->default(null)->columnSpanFull(),
                Map::make('location')
                    ->label('Location (Drag pin to set)')
                    ->columnSpanFull()
                    ->defaultLocation([30.0444, 31.2357])
                    ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set, ?array $state): void {
                        if (is_array($state) && isset($state['lat'], $state['lng'])) {
                            $set('lat', (float) $state['lat']);
                            $set('lng', (float) $state['lng']);
                        }
                    })
                    ->liveLocation(true, true, 5000)
                    ->showMarker()
                    ->markerColor('#E01B22')
                    ->showFullscreenControl()
                    ->showZoomControl()
                    ->draggable()
                    ->clickable(true)
                    ->hasSearchBox(true)
                    ->formatStateUsing(function ($record) {
                        if ($record && $record->lat && $record->lng) {
                            return ['lat' => (float) $record->lat, 'lng' => (float) $record->lng];
                        }
                        return ['lat' => 30.0444, 'lng' => 31.2357];
                    }),
                Hidden::make('lat')->default(30.0444),
                Hidden::make('lng')->default(31.2357),
            ]);
    }
}
