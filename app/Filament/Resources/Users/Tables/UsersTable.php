<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;


class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                IconColumn::make('is_admin')
                    ->boolean(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('age')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('city.name')
                    ->searchable(),
                TextColumn::make('vip_tier')
                    ->searchable(),
                TextColumn::make('vip_points')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('member_since')
                    ->date()
                    ->sortable(),
                IconColumn::make('profile_complete')
                    ->boolean(),
                TextColumn::make('avatar_url')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make("adjust_points")
                    ->action(function ($record, array $data) {
                        $record->points += $data["points_to_add"];
                        $record->save();
                    })
                    ->form([
                        TextInput::make("points_to_add")
                            ->label("Points to Add/Subtract")
                            ->numeric()
                            ->required(),
                    ])
                    ->icon("heroicon-o-star")
                    ->color("warning"),
                
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
