<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PointTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'pointTransactions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('points')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('points')
                    ->badge()
                    ->color(fn ($record) => $record->type === 'credit' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state, $record) => ($record->type === 'credit' ? '+' : '-').$state),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn ($state) => $state === 'credit' ? 'success' : 'danger'),
                TextColumn::make('description'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                // Read-only
            ])
            ->toolbarActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}
