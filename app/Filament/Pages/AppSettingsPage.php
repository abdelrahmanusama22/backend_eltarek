<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;

class AppSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

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

        $this->form->fill([
            // General
            'support_phone'         => AppSetting::get('support_phone', '19022'),
            'support_whatsapp'      => AppSetting::get('support_whatsapp', '+201000000000'),
            'compare_max'           => AppSetting::get('compare_max', 3),

            // Finance Calculator
            'finance_interest_rate' => $finance['interest_rate'] ?? AppSetting::get('finance_interest_rate', 15),
            'min_down_payment_pct'  => $finance['min_down_payment_pct'] ?? 20,
            'admin_fee_pct'         => $finance['admin_fee_pct'] ?? 1.5,
            'max_tenure_years'      => $finance['max_tenure_years'] ?? 7,

            // Banners & Promos
            'banner_title_ar'       => $banner['title_ar'] ?? 'عروض التمويل الحصرية',
            'banner_title_en'       => $banner['title_en'] ?? 'Exclusive Financing Offers',
            'banner_subtitle_ar'    => $banner['subtitle_ar'] ?? 'فائدة تبدأ من 0% بمقدم 30%',
            'banner_subtitle_en'    => $banner['subtitle_en'] ?? 'Interest starting from 0% with 30% down payment',
            'banner_active'         => $banner['active'] ?? true,

            // Holidays
            'blocked_dates'         => AppSetting::get('blocked_dates', []),
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
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // General
        AppSetting::put('support_phone', $data['support_phone']);
        AppSetting::put('support_whatsapp', $data['support_whatsapp']);
        AppSetting::put('compare_max', (int) $data['compare_max']);

        // Finance
        $finance = [
            'interest_rate'        => (float) $data['finance_interest_rate'],
            'min_down_payment_pct' => (float) $data['min_down_payment_pct'],
            'admin_fee_pct'        => (float) $data['admin_fee_pct'],
            'max_tenure_years'     => (int) $data['max_tenure_years'],
        ];
        AppSetting::put('finance', $finance);
        AppSetting::put('finance_interest_rate', (float) $data['finance_interest_rate']);

        // Banner
        $banner = [
            'active'      => (bool) ($data['banner_active'] ?? true),
            'title_ar'    => $data['banner_title_ar'] ?? '',
            'title_en'    => $data['banner_title_en'] ?? '',
            'subtitle_ar' => $data['banner_subtitle_ar'] ?? '',
            'subtitle_en' => $data['banner_subtitle_en'] ?? '',
        ];
        AppSetting::put('financing_banner', $banner);

        // Holidays
        AppSetting::put('blocked_dates', $data['blocked_dates'] ?? []);

        Notification::make()
            ->title('Settings updated and synced successfully!')
            ->success()
            ->send();
    }
}
