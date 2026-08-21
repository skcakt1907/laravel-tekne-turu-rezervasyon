<?php

namespace App\Filament\Owner\Resources\BlockedPeriods\Pages;

use App\Filament\Owner\Resources\BlockedPeriods\BlockedPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlockedPeriods extends ListRecords
{
    protected static string $resource = BlockedPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
