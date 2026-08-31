<?php

namespace App\Filament\Resources\Vehicles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('brand.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('model')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('model_ar')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('year')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('starting_price_egp')
                    ->numeric()
                    ->sortable(),
                ImageColumn::make('image_url')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('engine_summary')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('monthly_from_egp')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('badge')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sort')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('active')
                    ->boolean(),
            ])
            ->filters([
                TrashedFilter::make(),
                \Filament\Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->label('Brand'),
                \Filament\Tables\Filters\SelectFilter::make('year')
                    ->options(function () {
                        $years = \App\Models\Vehicle::select('year')->distinct()->pluck('year', 'year')->toArray();
                        arsort($years);
                        return $years;
                    })
                    ->label('Year'),
                \Filament\Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'SUV' => 'SUV',
                        'Sedan' => 'Sedan',
                        'Electric' => 'Electric',
                        'Coupe' => 'Coupe',
                        'Hatchback' => 'Hatchback',
                        'Pickup' => 'Pickup',
                        'Van' => 'Van',
                        'Other' => 'Other',
                    ])
                    ->label('Category'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->toolbarActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\VehicleExporter::class),
                \Filament\Actions\Action::make('import')
                    ->label('Import / Update Catalog')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => \App\Filament\Resources\Trims\TrimResource::getUrl('import')),
            ]);
    }
}
