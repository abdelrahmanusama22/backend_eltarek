<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use App\Models\Trim;
use App\Models\Vehicle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class AppSettingsPage extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected string $view = 'filament.pages.app-settings-page';

    public static function getNavigationGroup(): ?string
    {
        return 'Settings & Analytics';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationSort(): ?int
    {
        return 100;
    }

    public function getTitle(): string|Htmlable
    {
        return 'App Settings & Configurations';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $finance = AppSetting::get('finance', []);
        $banner = AppSetting::get('financing_banner', []);
        $testDriveTimes = AppSetting::get('test_drive_times', []);
        $defaultTimes = $testDriveTimes['default'] ?? ['10:00 AM', '1:00 PM', '4:00 PM'];

        $this->form->fill([
            // General
            'support_phone' => AppSetting::get('support_phone', '19022'),
            'support_whatsapp' => AppSetting::get('support_whatsapp', '+201000000000'),
            'compare_max' => AppSetting::get('compare_max', 3),

            // Finance Calculator
            'finance_interest_rate' => $finance['interest_rate'] ?? AppSetting::get('finance_interest_rate', 15),
            'min_down_payment_pct' => $finance['min_down_payment_pct'] ?? 20,
            'admin_fee_pct' => $finance['admin_fee_pct'] ?? 1.5,
            'max_tenure_years' => $finance['max_tenure_years'] ?? 7,

            // Banners & Promos
            'banner_title_ar' => $banner['title_ar'] ?? 'عروض التمويل الحصرية',
            'banner_title_en' => $banner['title_en'] ?? 'Exclusive Financing Offers',
            'banner_subtitle_ar' => $banner['subtitle_ar'] ?? 'فائدة تبدأ من 0% بمقدم 30%',
            'banner_subtitle_en' => $banner['subtitle_en'] ?? 'Interest starting from 0% with 30% down payment',
            'banner_active' => $banner['active'] ?? true,
            'home_hero_vehicle_ids' => AppSetting::get('home_hero_vehicle_ids', []),
            'home_smart_matches' => AppSetting::get('smart_matches', []),
            'home_budget_trim_ids' => AppSetting::get('budget_pick_trim_ids', []),

            // Holidays
            'blocked_dates' => AppSetting::get('blocked_dates', []),
            'test_drive_days_ahead' => $testDriveTimes['days_ahead'] ?? 7,
            'test_drive_min_notice_minutes' => $testDriveTimes['min_notice_minutes'] ?? 60,
            'test_drive_sun' => $testDriveTimes['sun'] ?? $defaultTimes,
            'test_drive_mon' => $testDriveTimes['mon'] ?? $defaultTimes,
            'test_drive_tue' => $testDriveTimes['tue'] ?? $defaultTimes,
            'test_drive_wed' => $testDriveTimes['wed'] ?? $defaultTimes,
            'test_drive_thu' => $testDriveTimes['thu'] ?? $defaultTimes,
            'test_drive_fri' => $testDriveTimes['fri'] ?? [],
            'test_drive_sat' => $testDriveTimes['sat'] ?? $defaultTimes,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('General & Support Settings')
                    ->description('Contact channels and general app parameters.')
                    ->schema([
                        TextInput::make('support_phone')
                            ->label('Support Hotline / Phone')
                            ->placeholder('19022')
                            ->required(),
                        TextInput::make('support_whatsapp')
                            ->label('Support WhatsApp Number')
                            ->placeholder('+201000000000')
                            ->required(),
                        TextInput::make('compare_max')
                            ->label('Max Vehicles in Comparison')
                            ->numeric()
                            ->minValue(2)
                            ->maxValue(5)
                            ->default(3)
                            ->required(),
                    ])->columns(3),

                Section::make('Finance Calculator Settings')
                    ->description('Rates and constraints for the in-app installment calculator.')
                    ->schema([
                        TextInput::make('finance_interest_rate')
                            ->label('Annual Interest Rate (%)')
                            ->numeric()
                            ->step('0.1')
                            ->required(),
                        TextInput::make('min_down_payment_pct')
                            ->label('Minimum Down Payment (%)')
                            ->numeric()
                            ->required(),
                        TextInput::make('admin_fee_pct')
                            ->label('Admin Fee (%)')
                            ->numeric()
                            ->step('0.1')
                            ->required(),
                        TextInput::make('max_tenure_years')
                            ->label('Max Loan Term (Years)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->required(),
                    ])->columns(4),

                Section::make('Promotional Banner (Home & Finance)')
                    ->description('Featured marketing banner shown inside the mobile application.')
                    ->schema([
                        Toggle::make('banner_active')
                            ->label('Display Promotional Banner')
                            ->default(true),
                        TextInput::make('banner_title_ar')
                            ->label('Banner Title (Arabic)'),
                        TextInput::make('banner_title_en')
                            ->label('Banner Title (English)'),
                        TextInput::make('banner_subtitle_ar')
                            ->label('Banner Subtitle (Arabic)'),
                        TextInput::make('banner_subtitle_en')
                            ->label('Banner Subtitle (English)'),
                    ])->columns(2),

                Section::make('Home page content')
                    ->description('Choose exactly what appears on the mobile Home page. Order selected records by dragging repeater rows.')
                    ->schema([
                        Select::make('home_hero_vehicle_ids')
                            ->label('Hero vehicles')
                            ->multiple()->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => self::searchVehicles($search))
                            ->getOptionLabelsUsing(fn (array $values): array => self::vehicleLabels($values))
                            ->maxItems(5),
                        Repeater::make('home_smart_matches')
                            ->label('Smart matches')
                            ->schema([
                                Select::make('trim_id')->label('Vehicle trim')->searchable()->required()
                                    ->getSearchResultsUsing(fn (string $search): array => self::searchTrims($search))
                                    ->getOptionLabelUsing(fn ($value): ?string => self::trimLabel($value)),
                                TextInput::make('match_percentage')->label('Match %')->numeric()->minValue(1)->maxValue(100)->required(),
                            ])->columns(2)->reorderable()->maxItems(8),
                        Select::make('home_budget_trim_ids')
                            ->label('Budget picks')
                            ->multiple()->searchable()->maxItems(8)
                            ->getSearchResultsUsing(fn (string $search): array => self::searchTrims($search))
                            ->getOptionLabelsUsing(fn (array $values): array => self::trimLabels($values)),
                    ])->collapsible()->hidden(),

                Section::make('Slot Management — Holidays & Blocked Dates')
                    ->description('Days when test-drives and branch visits are unavailable.')
                    ->schema([
                        Repeater::make('blocked_dates')
                            ->label('Blocked Dates / Holidays')
                            ->schema([
                                DatePicker::make('date')
                                    ->required(),
                                TextInput::make('reason')
                                    ->placeholder('e.g. National Holiday / Eid')
                                    ->label('Reason'),
                            ])
                            ->columns(2),
                    ]),

                Section::make('Test-drive weekly schedule')
                    ->description('Choose available times with one click. Leave every option unchecked to close that day. Changes are published to the mobile app after saving.')
                    ->schema([
                        TextInput::make('test_drive_days_ahead')
                            ->label('Days available ahead')
                            ->numeric()->integer()->minValue(1)->maxValue(60)->required(),
                        TextInput::make('test_drive_min_notice_minutes')
                            ->label('Minimum notice (minutes)')
                            ->numeric()->integer()->minValue(0)->maxValue(1440)->required(),
                        self::daySchedule('test_drive_sun', 'Sunday'),
                        self::daySchedule('test_drive_mon', 'Monday'),
                        self::daySchedule('test_drive_tue', 'Tuesday'),
                        self::daySchedule('test_drive_wed', 'Wednesday'),
                        self::daySchedule('test_drive_thu', 'Thursday'),
                        self::daySchedule('test_drive_fri', 'Friday'),
                        self::daySchedule('test_drive_sat', 'Saturday'),
                    ])->columns(1)->collapsible(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $finance = [
            'interest_rate' => (float) $data['finance_interest_rate'],
            'min_down_payment_pct' => (float) $data['min_down_payment_pct'],
            'admin_fee_pct' => (float) $data['admin_fee_pct'],
            'max_tenure_years' => (int) $data['max_tenure_years'],
        ];
        $banner = [
            'active' => (bool) ($data['banner_active'] ?? true),
            'title_ar' => $data['banner_title_ar'] ?? '',
            'title_en' => $data['banner_title_en'] ?? '',
            'subtitle_ar' => $data['banner_subtitle_ar'] ?? '',
            'subtitle_en' => $data['banner_subtitle_en'] ?? '',
        ];
        $testDriveTimes = [
            'days_ahead' => (int) $data['test_drive_days_ahead'],
            'min_notice_minutes' => (int) $data['test_drive_min_notice_minutes'],
        ];
        foreach (['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'] as $day) {
            $testDriveTimes[$day] = $this->normaliseTimes($data["test_drive_{$day}"] ?? []);
        }

        DB::transaction(function () use ($data, $finance, $banner, $testDriveTimes): void {
            AppSetting::put('support_phone', $data['support_phone']);
            AppSetting::put('support_whatsapp', $data['support_whatsapp']);
            AppSetting::put('compare_max', (int) $data['compare_max']);
            AppSetting::put('finance', $finance);
            AppSetting::put('finance_interest_rate', (float) $data['finance_interest_rate']);
            AppSetting::put('financing_banner', $banner);
            AppSetting::put('blocked_dates', $data['blocked_dates'] ?? []);
            AppSetting::put('test_drive_times', $testDriveTimes);
            AppSetting::put('home_hero_vehicle_ids', array_map('intval', $data['home_hero_vehicle_ids'] ?? []));
            AppSetting::put('smart_matches', collect($data['home_smart_matches'] ?? [])->map(fn (array $item): array => [
                'trim_id' => (int) $item['trim_id'],
                'match_percentage' => (int) $item['match_percentage'],
            ])->values()->all());
            AppSetting::put('budget_pick_trim_ids', array_map('intval', $data['home_budget_trim_ids'] ?? []));
        }, 3);

        Notification::make()
            ->title('Settings updated and synced successfully!')
            ->success()
            ->send();
    }

    /** @return array<int, string> */
    private function normaliseTimes(array $times): array
    {
        return collect($times)
            ->map(function (mixed $time): string {
                $value = trim((string) $time);
                try {
                    return \Carbon\Carbon::createFromFormat('g:i A', strtoupper($value))->format('g:i A');
                } catch (\Throwable) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'data.test_drive_times' => "Invalid time '{$value}'. Use a format like 10:00 AM.",
                    ]);
                }
            })
            ->unique()
            ->sortBy(fn (string $time): int => (int) \Carbon\Carbon::createFromFormat('g:i A', $time)->format('Hi'))
            ->values()
            ->all();
    }

    private static function daySchedule(string $name, string $label): CheckboxList
    {
        return CheckboxList::make($name)
            ->label($label)
            ->options(self::timeOptions())
            ->columns(6)
            ->gridDirection('row')
            ->bulkToggleable()
            ->helperText('No selected times = closed');
    }

    /** @return array<string, string> */
    private static function timeOptions(): array
    {
        $options = [];
        $time = \Carbon\Carbon::createFromTime(8, 0);
        $end = \Carbon\Carbon::createFromTime(21, 0);
        while ($time->lessThanOrEqualTo($end)) {
            $label = $time->format('g:i A');
            $options[$label] = $label;
            $time->addMinutes(30);
        }

        return $options;
    }

    /** @return array<int, string> */
    private static function searchVehicles(string $search): array
    {
        return Vehicle::query()
            ->where('active', true)
            ->where(function ($query) use ($search): void {
                $query->where('model', 'like', "%{$search}%")
                    ->orWhere('model_ar', 'like', "%{$search}%")
                    ->orWhereHas('brand', fn ($brand) => $brand
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%"));
            })
            ->with('brand')->orderBy('model')->limit(30)->get()
            ->mapWithKeys(fn (Vehicle $vehicle): array => [
                $vehicle->id => trim(($vehicle->brand?->name ?? '').' — '.$vehicle->model.' ('.$vehicle->year.')'),
            ])->all();
    }

    /** @return array<int, string> */
    private static function vehicleLabels(array $values): array
    {
        return Vehicle::with('brand')->where('active', true)->whereIn('id', $values)->get()
            ->mapWithKeys(fn (Vehicle $vehicle): array => [
                $vehicle->id => trim(($vehicle->brand?->name ?? '').' — '.$vehicle->model.' ('.$vehicle->year.')'),
            ])->all();
    }

    /** @return array<int, string> */
    private static function searchTrims(string $search): array
    {
        return Trim::query()->with('vehicle.brand')->where('active', true)
            ->whereHas('vehicle', fn ($vehicle) => $vehicle->where('active', true))
            ->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', fn ($vehicle) => $vehicle
                        ->where('model', 'like', "%{$search}%")
                        ->orWhere('model_ar', 'like', "%{$search}%"));
            })
            ->limit(30)->get()
            ->mapWithKeys(fn (Trim $trim): array => [$trim->id => self::formatTrimLabel($trim)])
            ->all();
    }

    private static function trimLabel(mixed $value): ?string
    {
        $trim = Trim::with('vehicle.brand')->where('active', true)->find($value);
        return $trim ? self::formatTrimLabel($trim) : null;
    }

    /** @return array<int, string> */
    private static function trimLabels(array $values): array
    {
        return Trim::with('vehicle.brand')->where('active', true)->whereIn('id', $values)->get()
            ->mapWithKeys(fn (Trim $trim): array => [$trim->id => self::formatTrimLabel($trim)])
            ->all();
    }

    private static function formatTrimLabel(Trim $trim): string
    {
        return trim(($trim->vehicle?->brand?->name ?? '').' — '.($trim->vehicle?->model ?? '').' — '.$trim->name);
    }
}
