<?php

namespace App\Filament\Resources\GarageLinkRequests\Pages;

use App\Filament\Resources\GarageLinkRequests\GarageLinkRequestResource;
use App\Models\GarageLinkRequest;
use App\Models\Trim;
use App\Models\Vehicle;
use App\Services\GarageLinkService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ViewGarageLinkRequest extends ViewRecord
{
    protected static string $resource = GarageLinkRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve & Link Car (الموافقة والربط)')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => $this->getRecord()->status === 'pending')
                ->form([
                    Select::make('vehicle_id')
                        ->label('Select Vehicle (اختر السيارة)')
                        ->options(fn () => Vehicle::with('brand')
                            ->where('active', true)
                            ->orderBy('model')
                            ->get()
                            ->mapWithKeys(fn (Vehicle $vehicle) => [
                                $vehicle->id => trim(($vehicle->brand?->name ? $vehicle->brand->name.' — ' : '').$vehicle->model.' ('.$vehicle->year.')'),
                            ]))
                        ->searchable()
                        ->live()
                        ->required()
                        ->afterStateUpdated(function ($state, Set $set) {
                            $set('trim_id', null);
                            $vehicle = Vehicle::find($state);
                            if ($vehicle) {
                                $set('name', $vehicle->model);
                            }
                        }),
                    Select::make('trim_id')
                        ->label('Select Trim (اختر الفئة)')
                        ->options(fn (Get $get) => $get('vehicle_id')
                            ? Trim::where('vehicle_id', $get('vehicle_id'))->where('active', true)->orderBy('name')->pluck('name', 'id')
                            : [])
                        ->searchable()
                        ->required()
                        ->disabled(fn (Get $get) => ! $get('vehicle_id')),
                    TextInput::make('name')
                        ->label('Display Name (اسم السيارة المعروض)')
                        ->required()
                        ->maxLength(120),
                    DatePicker::make('warranty_expires_at')
                        ->label('Warranty Expiry Date (تاريخ انتهاء الضمان)'),
                    DatePicker::make('next_service_at')
                        ->label('Next Service Due (موعد الصيانة القادم)'),
                ])
                ->fillForm(fn (GarageLinkRequest $record) => ['name' => $record->car_name])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    app(GarageLinkService::class)->approve($record, auth()->id(), $data + [
                        'warranty_active' => ! empty($data['warranty_expires_at']),
                        'vip_service' => false,
                    ]);

                    Notification::make()
                        ->title('Vehicle successfully linked to customer garage!')
                        ->success()
                        ->send();
                }),

            Action::make('reject')
                ->label('Reject Request (رفض الطلب)')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (): bool => $this->getRecord()->status === 'pending')
                ->form([
                    Textarea::make('admin_notes')
                        ->label('Reason for Rejection (سبب الرفض)')
                        ->placeholder('Explain to the customer why the chassis number could not be verified...')
                        ->required()
                        ->maxLength(1000),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    app(GarageLinkService::class)->reject($record, auth()->id(), $data['admin_notes']);

                    Notification::make()
                        ->title('Request rejected and notification sent to customer.')
                        ->warning()
                        ->send();
                }),
        ];
    }
}
