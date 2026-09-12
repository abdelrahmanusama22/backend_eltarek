<?php

namespace App\Filament\Resources\Trims;

use App\Filament\Resources\Trims\Pages\CreateTrim;
use App\Filament\Resources\Trims\Pages\EditTrim;
use App\Filament\Resources\Trims\Pages\ListTrims;
use App\Filament\Resources\Trims\Schemas\TrimForm;
use App\Filament\Resources\Trims\Tables\TrimsTable;
use App\Models\Trim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TrimResource extends Resource
{
    protected static ?string $model = Trim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    public static function getNavigationGroup(): ?string
    {
        return 'Catalog Management';
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return TrimForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrimsTable::configure($table);
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
            'index' => ListTrims::route('/'),
            'create' => CreateTrim::route('/create'),
            'edit' => EditTrim::route('/{record}/edit'),
        ];
    }
}
