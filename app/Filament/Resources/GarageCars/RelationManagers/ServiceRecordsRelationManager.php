<?php

namespace App\Filament\Resources\GarageCars\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceRecords';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('type')->required()->maxLength(80),
            DatePicker::make('serviced_at')->required(),
            TextInput::make('odometer_km')->numeric()->minValue(0),
            TextInput::make('service_center')->maxLength(255),
            Textarea::make('notes')->maxLength(2000),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('serviced_at')->date()->sortable(),
            TextColumn::make('type')->searchable(),
            TextColumn::make('odometer_km')->numeric()->suffix(' km'),
            TextColumn::make('service_center')->searchable(),
        ])->headerActions([CreateAction::make()])->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
