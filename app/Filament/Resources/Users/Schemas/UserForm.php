<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information (بيانات المستخدم)')
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->label('Profile Photo (الصورة الشخصية)')
                            ->image()
                            ->disk('public')
                            ->directory('avatars')
                            ->imageEditor()
                            ->maxSize(5120)
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Full Name (الاسم بالكامل)')
                            ->maxLength(255)
                            ->default(null),
                        TextInput::make('phone')
                            ->label('Phone Number (رقم الهاتف)')
                            ->tel()
                            ->nullable()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->helperText('Required for Phone-OTP users. Can be null for Email or Google registered users.'),
                        TextInput::make('email')
                            ->label('Email Address (البريد الإلكتروني)')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required(fn ($get): bool => (bool) $get('is_admin'))
                            ->nullable(fn ($get): bool => ! (bool) $get('is_admin'))
                            ->helperText(fn ($get): string => (bool) $get('is_admin')
                                ? 'Required for dashboard login access.'
                                : 'Registered email address (Email or Google login).'),
                        Toggle::make('is_active')
                            ->label('Active Status (الحساب مفعل)')
                            ->default(true),
                    ])->columns(2),

                Section::make('Administrative Access & Security (صلاحيات الإدارة)')
                    ->schema([
                        Toggle::make('is_admin')
                            ->label('Is Administrator? (مدير لوحة التحكم)')
                            ->live()
                            ->default(false),
                        TextInput::make('password')
                            ->label('Password (كلمة المرور)')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation, $get): bool => $operation === 'create' && (bool) $get('is_admin'))
                            ->visible(fn ($get): bool => (bool) $get('is_admin'))
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->maxLength(255)
                            ->helperText('Leave blank to keep current password.'),
                        Select::make('roles')
                            ->label('Assigned Roles (الأدوار والصلاحيات)')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->visible(fn ($get): bool => (bool) $get('is_admin')),
                    ])->columns(1),
            ]);
    }
}
