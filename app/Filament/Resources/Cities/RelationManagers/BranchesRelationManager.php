<?php

namespace App\Filament\Resources\Cities\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use App\Filament\Resources\Branches\Schemas\BranchForm;
use App\Filament\Resources\Branches\Tables\BranchesTable;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    public function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return BranchesTable::configure($table)
            ->heading('الفروع التابعة للمدينة')
            ->recordTitleAttribute('name')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
