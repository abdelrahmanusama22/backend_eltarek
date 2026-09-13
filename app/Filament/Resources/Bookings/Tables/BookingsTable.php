<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(fn ($state) => '#BK-'.(9000 + $state))
                    ->fontFamily('JetBrains Mono')
                    ->color('gray')
                    ->size('xs'),

                TextColumn::make('user.name')
                    ->label('CUSTOMER')
                    ->formatStateUsing(function (Booking $record) {
                        $name = $record->user->name ?? 'Unknown';
                        $initials = collect(explode(' ', $name))->map(fn ($n) => substr($n, 0, 1))->take(2)->implode('');
                        $phone = $record->user->phone ?? 'No phone';

                        return new HtmlString('
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-[#353439] flex items-center justify-center text-xs font-bold text-[#ffb4ab]">
                                    '.strtoupper($initials).'
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-white font-semibold">'.e($name).'</span>
                                    <span class="text-[#94949E] text-xs font-inter">'.e($phone).'</span>
                                </div>
                            </div>
                        ');
                    }),

                TextColumn::make('trim.name')
                    ->label('VEHICLE')
                    ->formatStateUsing(function ($state) {
                        return new HtmlString('
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[#ffb4ab] text-sm">directions_car</span>
                                <span class="text-white font-inter">'.e($state).'</span>
                            </div>
                        ');
                    }),

                TextColumn::make('branch.name')
                    ->label('BRANCH')
                    ->color('gray')
                    ->fontFamily('Inter'),

                TextColumn::make('date')
                    ->label('DATE & TIME')
                    ->formatStateUsing(function (Booking $record) {
                        $dateStr = Carbon::parse($record->date)->format('M d, Y');
                        $timeStr = $record->time ? Carbon::parse($record->time)->format('h:i A') : '';

                        return $timeStr ? "$dateStr - $timeStr" : $dateStr;
                    })
                    ->color('gray')
                    ->fontFamily('Inter'),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'completed' => 'primary',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->label('')
                    ->visible(fn (Booking $record) => $record->status === 'pending')
                    ->action(fn (Booking $record) => $record->update(['status' => 'confirmed'])),

                Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->label('')
                    ->visible(fn (Booking $record) => in_array($record->status, ['pending', 'confirmed']))
                    ->requiresConfirmation()
                    ->action(fn (Booking $record) => $record->update([
                        'status'=>'cancelled',
                        'slot_key'=>null,
                        'cancellation_reason'=>'Cancelled by administrator from bookings list.',
                    ])),

                EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->color('gray')
                    ->label(''),
            ])
            ->toolbarActions([])
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc');
    }
}
