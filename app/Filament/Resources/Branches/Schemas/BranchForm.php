<?php

namespace App\Filament\Resources\Branches\Schemas;

use Dotswan\MapPicker\Fields\Map;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('صورة الفرع')
                    ->schema([
                        FileUpload::make('image')
                            ->label('صورة أو شعار الفرع')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('branches')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->columnSpanFull(),
                    ]),
                Section::make('المعلومات العامة')
                    ->description('البيانات الأساسية للفرع')
                    ->schema([
                        TextInput::make('name')->label('الاسم (English)')->required(),
                        TextInput::make('name_ar')->label('الاسم (Arabic)')->required(),
                        Select::make('city_id')
                            ->relationship('city', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('المدينة'),
                        TextInput::make('phone')->label('رقم الهاتف')->tel()->required(),
                        TextInput::make('whatsapp')
                            ->label('رقم الواتساب')
                            ->tel()
                            ->nullable()
                            ->hint('اتركه فارغاً إذا كان هو نفس رقم الهاتف'),
                        TextInput::make('address')->label('العنوان (English)')->required()->columnSpanFull(),
                        TextInput::make('address_ar')->label('العنوان (Arabic)')->required()->columnSpanFull(),
                        Hidden::make('hours')->default(''),
                        Hidden::make('hours_ar')->default(''),
                        Repeater::make('opening_hours')
                            ->label('جدول العمل الأسبوعي')
                            ->helperText('هذا الجدول يحسب حالة مفتوح/مغلق في التطبيق تلقائياً حسب توقيت القاهرة.')
                            ->schema([
                                Select::make('day')->label('اليوم')->options([
                                    'sat' => 'السبت', 'sun' => 'الأحد', 'mon' => 'الاثنين', 'tue' => 'الثلاثاء',
                                    'wed' => 'الأربعاء', 'thu' => 'الخميس', 'fri' => 'الجمعة',
                                ])->required()->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                TimePicker::make('open')->label('يفتح')->seconds(false),
                                TimePicker::make('close')->label('يغلق')->seconds(false),
                                Toggle::make('closed')->label('مغلق طوال اليوم')->default(false),
                            ])
                            ->default([
                                ['day' => 'sat', 'open' => '09:00', 'close' => '22:00', 'closed' => false],
                                ['day' => 'sun', 'open' => '09:00', 'close' => '22:00', 'closed' => false],
                                ['day' => 'mon', 'open' => '09:00', 'close' => '22:00', 'closed' => false],
                                ['day' => 'tue', 'open' => '09:00', 'close' => '22:00', 'closed' => false],
                                ['day' => 'wed', 'open' => '09:00', 'close' => '22:00', 'closed' => false],
                                ['day' => 'thu', 'open' => '09:00', 'close' => '22:00', 'closed' => false],
                                ['day' => 'fri', 'open' => '09:00', 'close' => '22:00', 'closed' => true],
                            ])
                            ->required()->minItems(7)->maxItems(7)
                            ->columns(4)->reorderable(false)->addable(false)->deletable(false)->columnSpanFull(),
                        Hidden::make('timezone')->default('Africa/Cairo'),
                        Select::make('services')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options([
                                'Showroom' => 'معرض (Showroom)',
                                'Test Drive' => 'تجربة قيادة (Test Drive)',
                                'Finance Center' => 'خدمات التمويل (Finance Center)',
                                'Service & Maintenance' => 'صيانة وخدمة (Service & Maintenance)',
                                'sales' => 'مبيعات (Sales)',
                                'maintenance' => 'صيانة (Maintenance)',
                                'spare_parts' => 'قطع غيار (Spare Parts)',
                                'customer_service' => 'خدمة عملاء (Customer Service)',
                            ])
                            ->label('الخدمات المقدمة')
                            ->columnSpanFull(),
                        Hidden::make('is_open')->default(false),
                        Toggle::make('active')->label('مفعل')->required(),
                    ])->columns(2),

                Section::make('الموقع الجغرافي')
                    ->description('حدد موقع الفرع على الخريطة بدقة')
                    ->schema([
                        TextInput::make('google_map_link')
                            ->label('رابط خرائط جوجل (اختياري)')
                            ->hint('قم بلصق رابط خرائط جوجل (مثل https://maps.app.goo.gl/...) لاستخراج الإحداثيات تلقائياً')
                            ->url()
                            ->lazy()
                            ->afterStateUpdated(function ($state, $set) {
                                if (! $state) {
                                    return;
                                }
                                
                                $url = $state;
                                
                                if (str_contains($url, 'maps.app.goo.gl') || str_contains($url, 'goo.gl/maps') || str_contains($url, 'maps.google.com')) {
                                    $ch = curl_init($url);
                                    curl_setopt($ch, CURLOPT_HEADER, true);
                                    curl_setopt($ch, CURLOPT_NOBODY, true);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                                    // Set a user agent to prevent some servers from rejecting the request
                                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
                                    curl_exec($ch);
                                    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                                    curl_close($ch);
                                    if ($effectiveUrl) {
                                        $url = $effectiveUrl;
                                    }
                                }
                                
                                $lat = null;
                                $lng = null;
                                
                                if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
                                    $lat = (float) $matches[1];
                                    $lng = (float) $matches[2];
                                } elseif (preg_match('/query=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
                                    $lat = (float) $matches[1];
                                    $lng = (float) $matches[2];
                                } elseif (preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
                                    $lat = (float) $matches[1];
                                    $lng = (float) $matches[2];
                                } elseif (preg_match('/search\/(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
                                    $lat = (float) $matches[1];
                                    $lng = (float) $matches[2];
                                }

                                if ($lat !== null && $lng !== null) {
                                    $set('lat', $lat);
                                    $set('lng', $lng);
                                    $set('location', ['lat' => $lat, 'lng' => $lng]);
                                }
                            })
                            ->columnSpanFull(),
                        Map::make('location')
                            ->label('الموقع (قم بسحب الدبوس لتحديد المكان)')
                            ->columnSpanFull()
                            ->defaultLocation(30.0444, 31.2357)
                            ->afterStateUpdated(function ($set, ?array $state = null): void {
                                if (is_array($state) && isset($state['lat'], $state['lng'])) {
                                    $set('lat', (float) $state['lat']);
                                    $set('lng', (float) $state['lng']);
                                }
                            })
                            ->showMarker()
                            ->markerColor('#E01B22')
                            ->showFullscreenControl()
                            ->showZoomControl()
                            ->draggable()
                            ->clickable(true)
                            ->formatStateUsing(function ($record) {
                                if ($record && $record->lat && $record->lng) {
                                    return ['lat' => (float) $record->lat, 'lng' => (float) $record->lng];
                                }

                                return ['lat' => 30.0444, 'lng' => 31.2357];
                            }),
                        Hidden::make('lat')->default(30.0444),
                        Hidden::make('lng')->default(31.2357),
                    ]),
            ]);
    }
}
