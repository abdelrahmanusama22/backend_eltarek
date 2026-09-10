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
                            ->label('Official Price in EGP (السعر الرسمي بالجنيه)')
                            ->numeric()
                            ->default(null)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, $state) {
                                if (empty($state) || $state <= 0) {
                                    $set('active', false);
                                }
                            }),
                        TextInput::make('markup_percentage')
                            ->label('Markup Percentage (%)')
                            ->numeric()
                            ->default(5)
                            ->suffix('%')
                            ->helperText('Executive Price will be calculated automatically based on this %'),
                        \Filament\Forms\Components\Placeholder::make('executive_price_display')
                            ->label('Executive Price (Calculated)')
                            ->content(fn ($record) => $record ? number_format($record->executive_price) . ' EGP' : '-'),
                        TextInput::make('total_price')
                            ->label('Total Price (إجمالى السعر)')
                            ->numeric()
                            ->default(null),
                        TextInput::make('booking_deposit')
                            ->label('Booking Deposit (مقدم الحجز)')
                            ->numeric()
                            ->default(null),
                        TextInput::make('zero_interest_price')
                            ->label('Zero Interest Price (عرض زيرو فائدة)')
                            ->numeric()
                            ->default(null),
                        TextInput::make('price_9pct')
                            ->label('Installment Price 9% (سعر تقسيط 9%)')
                            ->numeric()
                            ->default(null),
                        TextInput::make('colors')
                            ->label('Available Colors (الألوان المتاحة)')
                            ->maxLength(255)
                            ->default(null),
                        \Filament\Forms\Components\Textarea::make('financing_notes')
                            ->label('Financing Notes (معلومات اضافية)')
                            ->rows(2)
                            ->default(null),
                        Toggle::make('is_on_hold')
                            ->label('Hold Status (موقوف/HOLD)')
                            ->default(false),
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
                                        \Filament\Schemas\Components\Group::make()
                                            ->schema([
                                                TextInput::make('specs.tech.engine')->label('Engine')->default(null),
                                                TextInput::make('specs.tech.hp')->label('Horsepower')->default(null),
                                                TextInput::make('specs.tech.transmission')->label('Transmission')->default(null),
                                            ])->columns(3),
                                        Repeater::make('specs.tech.custom_tech')
                                            ->label('Custom Tech Specs')
                                            ->schema([
                                                TextInput::make('label')->required(),
                                                TextInput::make('label_ar')->required(),
                                                TextInput::make('value')->required(),
                                            ])
                                            ->columns(3),
                                    ]),
                                Tab::make('Safety (أمان)')
                                    ->schema([
                                        \Filament\Schemas\Components\Group::make()
                                            ->schema([
                                                TextInput::make('specs.safety.airbags')->label('Airbags')->default(null),
                                                TextInput::make('specs.safety.abs_ebd')->label('ABS & EBD')->default(null),
                                            ])->columns(2),
                                        Repeater::make('specs.safety.custom_safety')
                                            ->label('Custom Safety Specs')
                                            ->schema([
                                                TextInput::make('label')->required(),
                                                TextInput::make('label_ar')->required(),
                                                TextInput::make('value')->required(),
                                            ])
                                            ->columns(3),
                                    ]),
                                Tab::make('Interior (مقصورة)')
                                    ->schema([
                                        \Filament\Schemas\Components\Group::make()
                                            ->schema([
                                                TextInput::make('specs.interior.seats_material')->label('Seats Material')->default(null),
                                                TextInput::make('specs.interior.screen_size')->label('Screen Size')->default(null),
                                            ])->columns(2),
                                        Repeater::make('specs.interior.custom_interior')
                                            ->label('Custom Interior Specs')
                                            ->schema([
                                                TextInput::make('label')->required(),
                                                TextInput::make('label_ar')->required(),
                                                TextInput::make('value')->required(),
                                            ])
                                            ->columns(3),
                                    ]),
                                Tab::make('Exterior (خارجي)')
                                    ->schema([
                                        \Filament\Schemas\Components\Group::make()
                                            ->schema([
                                                TextInput::make('specs.exterior.wheels_size')->label('Wheels Size')->default(null),
                                                TextInput::make('specs.exterior.sunroof')->label('Sunroof')->default(null),
                                            ])->columns(2),
                                        Repeater::make('specs.exterior.custom_exterior')
                                            ->label('Custom Exterior Specs')
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
