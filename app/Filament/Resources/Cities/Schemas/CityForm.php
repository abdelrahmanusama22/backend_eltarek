<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('معلومات المدينة')
                    ->description('أدخل بيانات المدينة وترتيبها')
                    ->schema([
                        TextInput::make('name')
                            ->label('الاسم (English)')
                            ->required(),
                        TextInput::make('name_ar')
                            ->label('الاسم (Arabic)')
                            ->required(),
                        TextInput::make('sort')
                            ->label('الترتيب (Sort)')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ])->columns(2),
            ]);
    }
}
