<?php

namespace App\Filament\Resources\GarageLinkRequests\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GarageLinkRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information (بيانات العميل)')
                    ->schema([
                        Placeholder::make('customer_name')
                            ->label('Customer Name (اسم العميل)')
                            ->content(fn ($record) => $record?->user?->name ?? '—'),
                        Placeholder::make('customer_phone')
                            ->label('Phone Number (رقم الهاتف)')
                            ->content(fn ($record) => $record?->user?->phone ?? '—'),
                        Placeholder::make('customer_email')
                            ->label('Email Address (البريد الإلكتروني)')
                            ->content(fn ($record) => $record?->user?->email ?? '—'),
                    ])->columns(3),

                Section::make('Vehicle & Chassis Verification (بيانات التحقق والشاسيه)')
                    ->schema([
                        Placeholder::make('identifier')
                            ->label('VIN / Tracking Code (رقم الشاسيه / كود التتبع)')
                            ->content(fn ($record) => $record?->identifier ?? '—'),
                        Placeholder::make('car_name')
                            ->label('Requested Vehicle Model (موديل السيارة المطلوب)')
                            ->content(fn ($record) => $record?->car_name ?? '—'),
                        Placeholder::make('status')
                            ->label('Request Status (حالة الطلب)')
                            ->content(fn ($record) => match ($record?->status) {
                                'approved' => 'Approved (تمت الموافقة والربط)',
                                'rejected' => 'Rejected (مرفوض)',
                                default => 'Pending (قيد المراجعة)',
                            }),
                    ])->columns(3),

                Section::make('Review & Audit Details (بيانات المراجعة والتدقيق)')
                    ->schema([
                        Placeholder::make('reviewed_by_user')
                            ->label('Reviewed By (المراجع)')
                            ->content(fn ($record) => $record?->reviewer?->name ?? '—'),
                        Placeholder::make('reviewed_at_date')
                            ->label('Review Date (تاريخ المراجعة)')
                            ->content(fn ($record) => $record?->reviewed_at?->format('Y-m-d H:i') ?? 'Not reviewed yet'),
                        Placeholder::make('admin_notes')
                            ->label('Admin Notes / Rejection Reason (ملاحظات المراجعة / سبب الرفض)')
                            ->content(fn ($record) => $record?->admin_notes ?: 'No notes provided.'),
                    ])->columns(3),
            ]);
    }
}
