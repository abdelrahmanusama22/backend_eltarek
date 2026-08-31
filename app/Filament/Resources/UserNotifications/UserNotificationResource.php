<?php

namespace App\Filament\Resources\UserNotifications;

use App\Filament\Resources\UserNotifications\Pages\CreateUserNotification;
use App\Filament\Resources\UserNotifications\Pages\EditUserNotification;
use App\Filament\Resources\UserNotifications\Pages\ListUserNotifications;
use App\Filament\Resources\UserNotifications\Schemas\UserNotificationForm;
use App\Filament\Resources\UserNotifications\Tables\UserNotificationsTable;
use App\Models\UserNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserNotificationResource extends Resource
{
    protected static ?string $model = UserNotification::class;

    public static function getNavigationGroup(): ?string
    {
        return 'App Engagement';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;




    public static function form(Schema $schema): Schema
    {
        return UserNotificationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserNotificationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserNotifications::route('/'),
            'create' => CreateUserNotification::route('/create'),
            'edit' => EditUserNotification::route('/{record}/edit'),
        ];
    }
}
