<?php

namespace App\Filament\Resources\Trims\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class TrimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Trim Details (تفاصيل الفئة)')
                    ->schema([
                        Select::make('vehicle_id')
                            ->relationship('vehicle', 'model')
                            ->required(),
                        TextInput::make('name')
                            ->label('Name (اسم الفئة انجليزي)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_ar')
                            ->label('Name AR (اسم الفئة عربي)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('price_egp')
                            ->label('Price in EGP (السعر بالجنيه)')
                            ->numeric()
                            ->default(null),
                        TextInput::make('original_price_egp')
                            ->label('Original Price EGP (السعر الأصلي)')
                            ->numeric()
                            ->default(null),
                        TextInput::make('subtitle')
                            ->label('Subtitle / Engine (المحرك / تفاصيل)')
                            ->required()
                            ->default(''),
                        Toggle::make('is_most_popular')
                            ->label('Most Popular (الأكثر مبيعاً)')
                            ->default(false),
                        Toggle::make('has_360_view')
                            ->label('Has 360 View (يوجد 360)')
                            ->default(false),
                        TextInput::make('view_360_url')
                            ->label('360 View URL')
                            ->url()
                            ->default(null),
                        TextInput::make('suggested_comparison_trim_id')
                            ->label('Suggested Rival Trim ID (رقم فئة المقارنة)')
                            ->numeric()
                            ->default(null),
                        Toggle::make('in_test_drive_fleet')
                            ->label('Available for Test Drive (متاح لتجربة القيادة)')
                            ->default(false),
                        TextInput::make('fleet_sort')
                            ->label('Fleet Sort Order')
                            ->numeric()
                            ->default(0),
                        Toggle::make('active')
                            ->label('Active (نشط)')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Gallery (صور الفئة)')
                    ->schema([
                        FileUpload::make('gallery')
                            ->label('Trim Images (الصور)')
                            ->multiple()
                            ->image()
                            ->disk('public')
                            ->directory('vehicles/gallery')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Highlights (أهم المواصفات السريعة)')
                    ->schema([
                        Repeater::make('highlights')
                            ->schema([
                                Select::make('icon')
                                    ->label('Icon')
                                    ->options([
                                        'engine' => 'Engine (محرك)',
                                        'transmission' => 'Transmission (ناقل حركة)',
                                        'safety' => 'Safety (أمان)',
                                        'power' => 'Power (قوة)',
                                        'wheel' => 'Steering/Drive (دفع)',
                                        'speed' => 'Speed (سرعة)',
                                    ])
                                    ->default('engine')
                                    ->required(),
                                TextInput::make('label')
                                    ->label('Label (EN)')
                                    ->required(),
                                TextInput::make('label_ar')
                                    ->label('Label (AR)')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Detailed Specs (المواصفات التفصيلية)')
                    ->schema([
                        Tabs::make('Specs Tabs')
                            ->tabs([
                                Tab::make('Tech (تكنولوجيا)')
                                    ->schema([
                                        Repeater::make('specs.tech')
                                            ->label('Tech Specs')
                                            ->schema([
                                                TextInput::make('label')->required(),
                                                TextInput::make('label_ar')->required(),
                                                TextInput::make('value')->required(),
                                            ])
                                            ->columns(3),
                                    ]),
                                Tab::make('Safety (أمان)')
                                    ->schema([
                                        Repeater::make('specs.safety')
                                            ->label('Safety Specs')
                                            ->schema([
                                                TextInput::make('label')->required(),
                                                TextInput::make('label_ar')->required(),
                                                TextInput::make('value')->required(),
                                            ])
                                            ->columns(3),
                                    ]),
                                Tab::make('Interior (مقصورة)')
                                    ->schema([
                                        Repeater::make('specs.int')
                                            ->label('Interior Specs')
                                            ->schema([
                                                TextInput::make('label')->required(),
                                                TextInput::make('label_ar')->required(),
                                                TextInput::make('value')->required(),
                                            ])
                                            ->columns(3),
                                    ]),
                                Tab::make('Exterior (خارجي)')
                                    ->schema([
                                        Repeater::make('specs.ext')
                                            ->label('Exterior Specs')
                                            ->schema([
                                                TextInput::make('label')->required(),
                                                TextInput::make('label_ar')->required(),
                                                TextInput::make('value')->required(),
                                            ])
                                            ->columns(3),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Metrics (مؤشرات الأداء)')
                    ->schema([
                        Tabs::make('Metrics Tabs')
                            ->tabs([
                                Tab::make('Horsepower')
                                    ->schema([
                                        TextInput::make('metrics.hp.display')->label('Display (e.g. 150 HP)'),
                                        TextInput::make('metrics.hp.score')->label('Score 1-100')->numeric(),
                                    ])->columns(2),
                                Tab::make('0-100 km/h')
                                    ->schema([
                                        TextInput::make('metrics.accel.display')->label('Display (e.g. 8.5s)'),
                                        TextInput::make('metrics.accel.score')->label('Score 1-100')->numeric(),
                                    ])->columns(2),
                                Tab::make('Top Speed')
                                    ->schema([
                                        TextInput::make('metrics.speed.display')->label('Display (e.g. 200 km/h)'),
                                        TextInput::make('metrics.speed.score')->label('Score 1-100')->numeric(),
                                    ])->columns(2),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
