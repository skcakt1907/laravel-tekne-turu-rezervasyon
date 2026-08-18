<?php

namespace App\Filament\Resources\Yachts\Pages;

use App\Filament\Concerns\SavesGuardedFields;
use App\Filament\Resources\Yachts\YachtResource;
use Filament\Resources\Pages\CreateRecord;

class CreateYacht extends CreateRecord
{
    use SavesGuardedFields;

    protected static string $resource = YachtResource::class;

    protected function guardedFormFields(): array
    {
        return ['status', 'is_featured', 'reject_reason'];
    }
}
