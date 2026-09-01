<?php

namespace App\Filament\Resources\Redemptions;

use App\Filament\Resources\Redemptions\Pages\CreateRedemption;
use App\Filament\Resources\Redemptions\Pages\EditRedemption;
use App\Filament\Resources\Redemptions\Pages\ListRedemptions;
use App\Filament\Resources\Redemptions\Schemas\RedemptionForm;
use App\Filament\Resources\Redemptions\Tables\RedemptionsTable;
use App\Models\Redemption;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RedemptionResource extends Resource
{
    protected static ?string $model = Redemption::class;

    public static function getNavigationGroup(): ?string
    {
        return 'App Engagement';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;




    public static function form(Schema $schema): Schema
    {
        return RedemptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RedemptionsTable::configure($table);
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
            'index' => ListRedemptions::route('/'),
            'create' => CreateRedemption::route('/create'),
            'edit' => EditRedemption::route('/{record}/edit'),
        ];
    }
}
