<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use App\Models\Trim;
use App\Models\Vehicle;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class HomeContentPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.home-content-page';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'App Engagement';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-home';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Home Page Content';
    }

    public function mount(): void
    {
        $this->form->fill([
            'hero_vehicle_ids' => AppSetting::get('home_hero_vehicle_ids', []),
            'smart_matches' => AppSetting::get('smart_matches', []),
            'budget_trim_ids' => AppSetting::get('budget_pick_trim_ids', []),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Hero slider')
                ->description('Choose up to five vehicles for the main slider.')
                ->schema([
                    Select::make('hero_vehicle_ids')->label('Hero vehicles')
                        ->multiple()->searchable()->maxItems(5)
                        ->getSearchResultsUsing(fn (string $search): array => self::searchVehicles($search))
                        ->getOptionLabelsUsing(fn (array $values): array => self::vehicleLabels($values)),
                ]),
            Section::make('Smart matches')
                ->description('Choose each trim and the match percentage shown in the app.')
                ->schema([
                    Repeater::make('smart_matches')->hiddenLabel()
                        ->schema([
                            Select::make('trim_id')->label('Vehicle trim')->searchable()->required()
                                ->getSearchResultsUsing(fn (string $search): array => self::searchTrims($search))
                                ->getOptionLabelUsing(fn ($value): ?string => self::trimLabel($value)),
                            TextInput::make('match_percentage')->label('Match %')
                                ->numeric()->minValue(1)->maxValue(100)->required(),
                        ])->columns(2)->reorderable()->maxItems(8)->collapsed(),
                ]),
            Section::make('Budget picks')
                ->description('Choose up to eight trims for the budget section.')
                ->schema([
                    Select::make('budget_trim_ids')->label('Budget trims')
                        ->multiple()->searchable()->maxItems(8)
                        ->getSearchResultsUsing(fn (string $search): array => self::searchTrims($search))
                        ->getOptionLabelsUsing(fn (array $values): array => self::trimLabels($values)),
                ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        DB::transaction(function () use ($data): void {
            AppSetting::put('home_hero_vehicle_ids', array_map('intval', $data['hero_vehicle_ids'] ?? []));
            AppSetting::put('smart_matches', collect($data['smart_matches'] ?? [])->map(fn (array $item): array => [
                'trim_id' => (int) $item['trim_id'],
                'match_percentage' => (int) $item['match_percentage'],
            ])->values()->all());
            AppSetting::put('budget_pick_trim_ids', array_map('intval', $data['budget_trim_ids'] ?? []));
        }, 3);

        Notification::make()->title('Home content updated successfully')->success()->send();
    }

    private static function searchVehicles(string $search): array
    {
        return Vehicle::with('brand')->where('active', true)
            ->where(fn ($query) => $query->where('model', 'like', "%{$search}%")
                ->orWhere('model_ar', 'like', "%{$search}%")
                ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', "%{$search}%")))
            ->orderBy('model')->limit(25)->get()
            ->mapWithKeys(fn (Vehicle $vehicle): array => [$vehicle->id => self::vehicleLabel($vehicle)])->all();
    }

    private static function vehicleLabels(array $values): array
    {
        return Vehicle::with('brand')->where('active', true)->whereIn('id', $values)->get()
            ->mapWithKeys(fn (Vehicle $vehicle): array => [$vehicle->id => self::vehicleLabel($vehicle)])->all();
    }

    private static function vehicleLabel(Vehicle $vehicle): string
    {
        return trim(($vehicle->brand?->name ?? '').' — '.$vehicle->model.' ('.$vehicle->year.')');
    }

    private static function searchTrims(string $search): array
    {
        return Trim::with('vehicle.brand')->where('active', true)
            ->whereHas('vehicle', fn ($vehicle) => $vehicle->where('active', true))
            ->where(fn ($query) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('name_ar', 'like', "%{$search}%")
                ->orWhereHas('vehicle', fn ($vehicle) => $vehicle->where('model', 'like', "%{$search}%")))
            ->limit(25)->get()
            ->mapWithKeys(fn (Trim $trim): array => [$trim->id => self::trimText($trim)])->all();
    }

    private static function trimLabel(mixed $value): ?string
    {
        $trim = Trim::with('vehicle.brand')->where('active', true)->find($value);
        return $trim ? self::trimText($trim) : null;
    }

    private static function trimLabels(array $values): array
    {
        return Trim::with('vehicle.brand')->where('active', true)->whereIn('id', $values)->get()
            ->mapWithKeys(fn (Trim $trim): array => [$trim->id => self::trimText($trim)])->all();
    }

    private static function trimText(Trim $trim): string
    {
        return trim(($trim->vehicle?->brand?->name ?? '').' — '.($trim->vehicle?->model ?? '').' — '.$trim->name);
    }
}
