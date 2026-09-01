<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use App\Filament\Exports\UserExporter;
class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(UserExporter::class)
            ])
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('points')
                    ->label('Loyalty Points')
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                IconColumn::make('is_admin')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_admin')
                    ->label('المديرون')
            ])
            ->recordActions([
                EditAction::make(),
                Action::make("adjust_points")
                    ->label('Manage Points')
                    ->action(function ($record, array $data) {
                        $record->addPoints(
                            $data['points'], 
                            $data['description'], 
                            $data['type']
                        );
                    })
                    ->form([
                        \Filament\Forms\Components\Select::make('type')
                            ->options([
                                'credit' => 'Add Points (+)',
                                'debit' => 'Deduct Points (-)'
                            ])
                            ->required()
                            ->default('credit'),
                        TextInput::make('points')
                            ->label('Amount')
                            ->numeric()
                            ->required(),
                        TextInput::make('description')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->icon("heroicon-o-star")
                    ->color("warning"),
                
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->exporter(UserExporter::class),
                ]),
            ]);
    }
}
