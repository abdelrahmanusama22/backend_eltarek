<?php

namespace App\Filament\Resources\Redemptions\Tables;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RedemptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reward.name')
                    ->label('Reward')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('points_spent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->date()
                    ->sortable(),
                TextColumn::make('effective_status')->label('Status')->badge()->color(fn ($state)=>match($state){'active'=>'success','used'=>'info','cancelled'=>'danger',default=>'warning'}),
                TextColumn::make('used_at')->dateTime()->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('mark_used')->label('Mark used')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->visible(fn ($record)=>$record->effective_status === 'active')
                    ->action(function ($record) { $record->update(['status'=>'used','used_at'=>now(),'used_by'=>auth()->id()]); Notification::make()->title('Redemption marked as used')->success()->send(); }),
            ]);
    }
}
