<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\SavesGuardedFields;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use SavesGuardedFields;

    protected static string $resource = UserResource::class;

    protected function guardedFormFields(): array
    {
        return ['role', 'is_approved', 'is_active', 'notes'];
    }
}
