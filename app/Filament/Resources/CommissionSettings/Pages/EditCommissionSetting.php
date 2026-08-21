<?php

namespace App\Filament\Resources\CommissionSettings\Pages;

use App\Filament\Resources\CommissionSettings\CommissionSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommissionSetting extends EditRecord
{
    protected static string $resource = CommissionSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
