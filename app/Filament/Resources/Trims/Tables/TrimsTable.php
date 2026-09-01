<?php

namespace App\Filament\Resources\Trims\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;

class TrimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehicle.id')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('name_ar')
                    ->searchable(),
                TextColumn::make('price_egp')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('original_price_egp')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_most_popular')
                    ->boolean(),
                TextColumn::make('subtitle')
                    ->searchable(),
                IconColumn::make('has_360_view')
                    ->boolean(),
                TextColumn::make('view_360_url')
                    ->searchable(),
                TextColumn::make('suggested_comparison_trim_id')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('in_test_drive_fleet')
                    ->boolean(),
                TextColumn::make('fleet_sort')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('active')
                    ->boolean(),
            ])
            ->filters([
                TrashedFilter::make(),
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
