<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Booking;

class LatestBookings extends BaseWidget
{
    protected static ?string $heading = 'Recent Bookings';
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()->with(['user', 'trim', 'branch'])->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(fn ($state) => '#BK-' . (9000 + $state)),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('CUSTOMER')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('trim.name')
                    ->label('VEHICLE'),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('BRANCH'),
                Tables\Columns\TextColumn::make('date')
                    ->label('DATE')
                    ->date('M d, h:i A'),
                Tables\Columns\TextColumn::make('status')
                    ->label('STATUS')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'danger', // Mockup shows red for pending
                        'confirmed' => 'success', // Mockup shows green for confirmed
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('view')
                    ->url(fn (Booking $record): string => route('filament.admin.resources.bookings.edit', $record))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->label(''),
            ])
            ->paginated(false);
    }
}
