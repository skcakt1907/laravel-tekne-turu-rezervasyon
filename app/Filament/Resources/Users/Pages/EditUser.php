<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\SavesGuardedFields;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use SavesGuardedFields;

    protected static string $resource = UserResource::class;

    protected function guardedFormFields(): array
    {
        return ['role', 'is_approved', 'is_active', 'notes'];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
