<?php

namespace App\Filament\Resources\OtpLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OtpLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_verified')
                    ->label('Is Verified')
                    ->boolean()
                    ->state(fn ($record) => $record->verified_at !== null),
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

            ])
            ->toolbarActions([
                BulkActionGroup::make([

                ]),
            ]);
    }
}
