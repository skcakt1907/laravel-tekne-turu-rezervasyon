<?php

namespace App\Filament\Resources\Yachts\Pages;

use App\Filament\Concerns\FillsTranslations;
use App\Filament\Concerns\SavesGuardedFields;
use App\Filament\Resources\Yachts\YachtResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditYacht extends EditRecord
{
    use FillsTranslations;
    use SavesGuardedFields;

    protected static string $resource = YachtResource::class;

    protected function guardedFormFields(): array
    {
        return ['status', 'is_featured', 'reject_reason'];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
