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
                TextColumn::make('vehicle.brand.name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vehicle.model')
                    ->label('Model')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Trim Name')
                    ->searchable(),
                TextColumn::make('price_egp')
                    ->label('Official Price EGP')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('markup_percentage')
                    ->label('Markup %')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('executive_price')
                    ->label('Executive Price EGP')
                    ->state(fn (\App\Models\Trim $record) => $record->executive_price)
                    ->numeric()
                    ->sortable(false),
                IconColumn::make('is_on_hold')
                    ->label('Hold')
                    ->boolean(),
                TextColumn::make('colors')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('booking_deposit')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_price')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_most_popular')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('active')
                    ->boolean(),
            ])
            ->filters([
                TrashedFilter::make(),
                \Filament\Tables\Filters\TernaryFilter::make('is_on_hold')
                    ->label('Hold Status'),
                \Filament\Tables\Filters\SelectFilter::make('brand')
                    ->relationship('vehicle.brand', 'name')
                    ->label('Brand'),
                \Filament\Tables\Filters\SelectFilter::make('year')
                    ->options(function () {
                        $years = \App\Models\Vehicle::select('year')->distinct()->pluck('year', 'year')->toArray();
                        arsort($years);
                        return $years;
                    })
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('vehicle', fn ($q) => $q->where('year', $data['value']));
                        }
                    })
                    ->label('Year'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('updateMarkup')
                        ->label('Update Markup %')
                        ->icon('heroicon-o-currency-dollar')
                        ->form([
                            \Filament\Forms\Components\TextInput::make('markup_percentage')
                                ->label('Markup Percentage (%)')
                                ->numeric()
                                ->required()
                                ->default(5)
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['markup_percentage' => $data['markup_percentage']]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->toolbarActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\TrimExporter::class),
                \Filament\Actions\Action::make('import')
                    ->label('Import / Update Catalog')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => \App\Filament\Resources\Trims\TrimResource::getUrl('import')),
            ]);
    }
}
