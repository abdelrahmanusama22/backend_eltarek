<?php

namespace App\Filament\Resources\Trims\Tables;

use App\Filament\Exports\TrimExporter;
use App\Models\Trim;
use App\Models\Vehicle;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
                    ->state(fn (Trim $record) => $record->executive_price)
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
                TernaryFilter::make('is_on_hold')
                    ->label('Hold Status'),
                SelectFilter::make('brand')
                    ->relationship('vehicle.brand', 'name')
                    ->label('Brand'),
                SelectFilter::make('year')
                    ->options(function () {
                        $years = Vehicle::select('year')->distinct()->pluck('year', 'year')->toArray();
                        arsort($years);

                        return $years;
                    })
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('vehicle', fn ($q) => $q->where('year', $data['value']));
                        }
                    })
                    ->label('Year'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(), // ضفت لك زرار المسح الفردي زي العربيات
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('updateMarkup')
                        ->label('Update Markup %')
                        ->icon('heroicon-o-currency-dollar')
                        ->form([
                            TextInput::make('markup_percentage')
                                ->label('Markup Percentage (%)')
                                ->numeric()
                                ->required()
                                ->default(5),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['markup_percentage' => $data['markup_percentage']]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(TrimExporter::class),
            ]);
    }
}
