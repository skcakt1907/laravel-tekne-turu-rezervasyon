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

    /**
     * owner_id formda yok ama kolon zorunlu.
     *
     * Tur sahibi diye ayri bir taraf kalmadi; butun turlar firmaya ait.
     * Kolonun kendisi duruyor (eski kayitlarin bagi ve ileride birden
     * fazla isletme olma ihtimali icin), turu ekleyen yonetici yaziliyor.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_id'] ??= auth()->id();

        return $data;
    }
}
