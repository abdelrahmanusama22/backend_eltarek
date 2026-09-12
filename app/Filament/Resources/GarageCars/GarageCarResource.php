<?php

namespace App\Filament\Resources\GarageCars;

use App\Filament\Resources\GarageCars\Pages\CreateGarageCar;
use App\Filament\Resources\GarageCars\Pages\EditGarageCar;
use App\Filament\Resources\GarageCars\Pages\ListGarageCars;
use App\Filament\Resources\GarageCars\Schemas\GarageCarForm;
use App\Filament\Resources\GarageCars\Tables\GarageCarsTable;
use App\Filament\Resources\GarageCars\RelationManagers\ServiceRecordsRelationManager;
use App\Models\GarageCar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GarageCarResource extends Resource
{
    protected static ?string $model = GarageCar::class;

    public static function getNavigationGroup(): ?string
    {
        return 'App Engagement';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    public static function form(Schema $schema): Schema
    {
        return GarageCarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GarageCarsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ServiceRecordsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGarageCars::route('/'),
            'create' => CreateGarageCar::route('/create'),
            'edit' => EditGarageCar::route('/{record}/edit'),
        ];
    }
}
