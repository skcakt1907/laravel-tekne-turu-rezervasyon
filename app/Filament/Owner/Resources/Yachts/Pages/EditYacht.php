<?php

namespace App\Filament\Owner\Resources\Yachts\Pages;

use App\Filament\Concerns\FillsTranslations;
use App\Filament\Owner\Resources\Yachts\YachtResource;
use Filament\Resources\Pages\EditRecord;

class EditYacht extends EditRecord
{
    use FillsTranslations;

    protected static string $resource = YachtResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
