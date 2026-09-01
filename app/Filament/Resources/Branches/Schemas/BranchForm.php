<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Dotswan\MapPicker\Fields\Map;
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
                            ->directory('branches')
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
                        TextInput::make('hours')->label('مواعيد العمل (English)')->required(),
                        TextInput::make('hours_ar')->label('مواعيد العمل (Arabic)')->required(),
                        Select::make('services')
                            ->multiple()
                            ->options([
                                'sales' => 'مبيعات',
                                'maintenance' => 'صيانة',
                                'spare_parts' => 'قطع غيار',
                                'customer_service' => 'خدمة عملاء',
                            ])
                            ->label('الخدمات المقدمة')
                            ->columnSpanFull(),
                        Toggle::make('is_open')->label('مفتوح الآن')->required(),
                        Toggle::make('active')->label('مفعل')->required(),
                    ])->columns(2),

                Section::make('الموقع الجغرافي')
                    ->description('حدد موقع الفرع على الخريطة بدقة')
                    ->schema([
                        Map::make('location')
                            ->label('الموقع (قم بسحب الدبوس لتحديد المكان)')
                            ->columnSpanFull()
                            ->defaultLocation(30.0444, 31.2357)
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
