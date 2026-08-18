<?php

namespace App\Filament\Concerns;

/**
 * Edit sayfasında formu doldururken çevrilebilir alanları ham dizi hâline
 * getirir; aksi hâlde spatie yalnızca aktif dildeki metni döndürür ve
 * diğer dilin sekmesi boş görünür.
 */
trait FillsTranslations
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! method_exists($record, 'getTranslatableAttributes')) {
            return $data;
        }

        foreach ($record->getTranslatableAttributes() as $attribute) {
            $data[$attribute] = $record->getTranslations($attribute);
        }

        return $data;
    }
}
