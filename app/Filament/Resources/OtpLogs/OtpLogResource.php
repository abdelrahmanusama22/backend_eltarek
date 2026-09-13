<?php

namespace App\Filament\Resources\OtpLogs;

use App\Filament\Resources\OtpLogs\Pages\CreateOtpLog;
use App\Filament\Resources\OtpLogs\Pages\EditOtpLog;
use App\Filament\Resources\OtpLogs\Pages\ListOtpLogs;
use App\Filament\Resources\OtpLogs\Schemas\OtpLogForm;
use App\Filament\Resources\OtpLogs\Tables\OtpLogsTable;
use App\Models\OtpCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OtpLogResource extends Resource
{
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    protected static ?string $model = OtpCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    public static function getNavigationGroup(): ?string
    {
        return 'User Management';
    }

    public static function form(Schema $schema): Schema
    {
        return OtpLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OtpLogsTable::configure($table);
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
            'index' => ListOtpLogs::route('/'),
            'create' => CreateOtpLog::route('/create'),
            'edit' => EditOtpLog::route('/{record}/edit'),
        ];
    }
}
