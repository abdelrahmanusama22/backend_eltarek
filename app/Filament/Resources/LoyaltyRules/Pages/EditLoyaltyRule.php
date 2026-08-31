<?php

namespace App\Filament\Resources\LoyaltyRules\Pages;

use App\Filament\Resources\LoyaltyRules\LoyaltyRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoyaltyRule extends EditRecord
{
    protected static string $resource = LoyaltyRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
