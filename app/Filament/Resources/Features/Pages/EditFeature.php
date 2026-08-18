<?php

namespace App\Filament\Resources\Features\Pages;

use App\Filament\Concerns\FillsTranslations;
use App\Filament\Resources\Features\FeatureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeature extends EditRecord
{
    use FillsTranslations;

    protected static string $resource = FeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
