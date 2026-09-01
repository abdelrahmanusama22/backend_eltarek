<?php

namespace App\Filament\Resources\GarageCars\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;


class GarageCarsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tracking_code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                ImageColumn::make('image_url'),
                IconColumn::make('warranty_active')
                    ->boolean(),
                TextColumn::make('warranty_expires_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('next_service_at')
                    ->date()
                    ->sortable(),
                IconColumn::make('vip_service')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make("generate_tracking")
                    ->action(function ($record) {
                        $record->tracking_code = strtoupper(Str::random(10));
                        $record->save();
                    })
                    ->icon("heroicon-o-qr-code")
                    ->color("success")
                    ->requiresConfirmation(),
                
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
