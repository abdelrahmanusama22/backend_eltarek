<?php

namespace App\Filament\Resources\Brands\Tables;

use App\Filament\Exports\BrandExporter;
use App\Filament\Resources\Trims\TrimResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('name_ar')
                    ->searchable(),
                TextColumn::make('vehicles_count')
                    ->label('Vehicles')
                    ->counts('vehicles')
                    ->sortable(),
                TextColumn::make('tagline')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tagline_ar')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('monogram')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tier')
                    ->searchable(),
                TextColumn::make('logo_url')
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
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->toolbarActions([
                ExportAction::make()
                    ->exporter(BrandExporter::class),
                Action::make('import')
                    ->label('Import / Update Catalog')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => TrimResource::getUrl('import')),
            ]);
    }
}
