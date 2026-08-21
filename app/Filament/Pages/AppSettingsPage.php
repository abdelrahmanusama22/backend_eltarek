<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
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

    public static function getNavigationUrl(): string
    {
        return '#';
    }

    public static function getNavigationBadge(): ?string
    {
        return '??????';
    }



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
        return 'App Settings';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'finance_interest_rate' => AppSetting::get('finance_interest_rate', 15),
            'support_phone'         => AppSetting::get('support_phone', '01000000000'),
            'blocked_dates'         => AppSetting::get('blocked_dates', []),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('General Settings')->schema([
                    TextInput::make('finance_interest_rate')
                        ->label('Finance Interest Rate (%)')
                        ->numeric()
                        ->required(),
                    TextInput::make('support_phone')
                        ->label('Support Phone Number')
                        ->tel()
                        ->required(),
                ]),
                Section::make('Slot Management Ã¢â‚¬â€ Holidays')->schema([
                    Repeater::make('blocked_dates')
                        ->label('Blocked Dates / Holidays')
                        ->schema([
                            DatePicker::make('date')
                                ->required(),
                            TextInput::make('reason')
                                ->label('Reason (e.g. Eid Holiday)'),
                        ])
                        ->columns(2),
                ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        AppSetting::put('finance_interest_rate', $data['finance_interest_rate']);
        AppSetting::put('support_phone', $data['support_phone']);
        AppSetting::put('blocked_dates', $data['blocked_dates']);

        Notification::make()
            ->title('Settings saved successfully!')
            ->success()
            ->send();
    }
}
