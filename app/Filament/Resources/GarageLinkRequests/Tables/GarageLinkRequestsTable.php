<?php
namespace App\Filament\Resources\GarageLinkRequests\Tables;
use App\Models\GarageLinkRequest;
use App\Models\Trim;
use App\Models\Vehicle;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Services\GarageLinkService;
class GarageLinkRequestsTable {
    public static function configure(Table $table): Table {
        return $table->columns([
            TextColumn::make('user.name')->label('Customer')->searchable(),
            TextColumn::make('user.email')->label('Email')->searchable(),
            TextColumn::make('identifier')->label('VIN / Tracking Code')->searchable()->copyable(),
            TextColumn::make('car_name')->label('Requested Car'),
            TextColumn::make('status')->badge()->color(fn(string $state)=>match($state){'approved'=>'success','rejected'=>'danger',default=>'warning'}),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->filters([SelectFilter::make('status')->options(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'])])
        ->recordActions([
            Action::make('approve')->color('success')->icon('heroicon-o-check')->form([
                Select::make('vehicle_id')->label('Vehicle / السيارة')
                    ->options(fn()=>Vehicle::with('brand')->where('active', true)->orderBy('model')->get()
                        ->mapWithKeys(fn(Vehicle $vehicle)=>[$vehicle->id => trim(($vehicle->brand?->name ? $vehicle->brand->name.' — ' : '').$vehicle->model.' ('.$vehicle->year.')')]))
                    ->searchable()->live()->required()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $set('trim_id', null);
                        $vehicle = Vehicle::find($state);
                        if ($vehicle) $set('name', $vehicle->model);
                    }),
                Select::make('trim_id')->label('Trim / الفئة')
                    ->options(fn(Get $get)=>$get('vehicle_id') ? Trim::where('vehicle_id', $get('vehicle_id'))->where('active', true)->orderBy('name')->pluck('name','id') : [])
                    ->searchable()->required()->disabled(fn(Get $get)=>!$get('vehicle_id')),
                TextInput::make('name')->label('Display name / اسم السيارة')->required()->maxLength(120),
                DatePicker::make('warranty_expires_at'), DatePicker::make('next_service_at'),
            ])->fillForm(fn(GarageLinkRequest $record)=>['name'=>$record->car_name])->visible(fn(GarageLinkRequest $record)=>$record->status==='pending')->action(function(GarageLinkRequest $record,array $data){
                app(GarageLinkService::class)->approve($record, auth()->id(), $data + ['warranty_active'=>!empty($data['warranty_expires_at']), 'vip_service'=>false]);
                Notification::make()->title('Vehicle linked to customer')->success()->send();
            }),
            Action::make('reject')->color('danger')->icon('heroicon-o-x-mark')->form([Textarea::make('admin_notes')->required()->maxLength(1000)])->visible(fn(GarageLinkRequest $record)=>$record->status==='pending')->action(function(GarageLinkRequest $record,array $data){app(GarageLinkService::class)->reject($record, auth()->id(), $data['admin_notes']); Notification::make()->title('Request rejected')->success()->send();}),
        ])->defaultSort('created_at','desc');
    }
}
