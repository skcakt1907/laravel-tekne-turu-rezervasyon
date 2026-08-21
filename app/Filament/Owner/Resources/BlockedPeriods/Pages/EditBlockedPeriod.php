<?php

namespace App\Filament\Owner\Resources\BlockedPeriods\Pages;

use App\Filament\Owner\Resources\BlockedPeriods\BlockedPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlockedPeriod extends EditRecord
{
    protected static string $resource = BlockedPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
