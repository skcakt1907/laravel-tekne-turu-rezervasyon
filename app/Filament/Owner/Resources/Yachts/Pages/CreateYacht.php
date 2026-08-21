<?php

namespace App\Filament\Owner\Resources\Yachts\Pages;

use App\Enums\YachtStatus;
use App\Filament\Owner\Resources\Yachts\YachtResource;
use Filament\Resources\Pages\CreateRecord;

class CreateYacht extends CreateRecord
{
    protected static string $resource = YachtResource::class;

    /**
     * Sahip ve durum formdan DEGIL sunucudan gelir: yat sahibi kendi ilanini
     * baskasinin ustune yazamaz, dogrudan yayina da alamaz.
     */
    protected function afterCreate(): void
    {
        $this->record->forceFill([
            'status' => YachtStatus::Draft,
        ])->save();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_id'] = auth()->id();

        return $data;
    }
}
