<?php
namespace App\Filament\Resources\GarageLinkRequests;
use App\Filament\Resources\GarageLinkRequests\Pages\ListGarageLinkRequests;
use App\Filament\Resources\GarageLinkRequests\Tables\GarageLinkRequestsTable;
use App\Models\GarageLinkRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
class GarageLinkRequestResource extends Resource {
    protected static ?string $model = GarageLinkRequest::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;
    protected static ?string $navigationLabel = 'Garage Link Requests';
    public static function getNavigationGroup(): ?string { return 'App Engagement'; }
    public static function table(Table $table): Table { return GarageLinkRequestsTable::configure($table); }
    public static function getPages(): array { return ['index'=>ListGarageLinkRequests::route('/')]; }
}
