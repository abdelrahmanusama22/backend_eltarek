<?php

namespace App\Filament\Resources\Rewards\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RewardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('name_ar')
                    ->searchable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('description_ar')
                    ->searchable(),
                TextColumn::make('points_cost')
                    ->suffix(' pts')
                    ->sortable(),
                TextColumn::make('stock')->placeholder('Unlimited'),
                TextColumn::make('per_user_limit')->label('Limit/customer')->placeholder('Unlimited'),
                TextColumn::make('validity_days')->suffix(' days'),
                IconColumn::make('active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
