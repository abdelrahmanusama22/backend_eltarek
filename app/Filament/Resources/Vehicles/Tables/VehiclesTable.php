<?php

namespace App\Filament\Resources\Vehicles\Tables;

use App\Models\Vehicle;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use App\Filament\Exports\VehicleExporter;

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
                SelectColumn::make('badge')
                    ->options([
                        'New Arrival' => 'New Arrival',
                        'Best Seller' => 'Best Seller',
                        'Exclusive' => 'Exclusive',
                        'Luxury Pick' => 'Luxury Pick',
                    ])
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sort')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('active')
                    ->boolean(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->label('Brand'),
                SelectFilter::make('year')
                    ->options(function () {
                        $years = Vehicle::select('year')->distinct()->pluck('year', 'year')->toArray();
                        arsort($years);
                        return $years;
                    })
                    ->label('Year'),
                SelectFilter::make('category')
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
            ->actions([
                EditAction::make(),
                DeleteAction::make(), // ده الزرار الفردي اللي بيمسح عربية واحدة
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(), // ده اللي بيفعل مربعات التحديد عشان تمسح كذا عربية
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(VehicleExporter::class),
                Action::make('import')
                    ->label('Import / Update Catalog')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => \App\Filament\Resources\Trims\TrimResource::getUrl('import')),
            ]);
    }
}