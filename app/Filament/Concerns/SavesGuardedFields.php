<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Arr;

/**
 * Mass-assignment'a kapalı alanları (status, is_featured, role vb.) form
 * kaydedildikten sonra forceFill ile yazar. Böylece modeldeki $fillable
 * koruması kalır ama panel bu alanları yönetebilir.
 *
 * Kullanan sayfa `guardedFormFields()` ile beyaz listeyi verir.
 */
trait SavesGuardedFields
{
    abstract protected function guardedFormFields(): array;

    protected function afterCreate(): void
    {
        $this->persistGuardedFields();
    }

    protected function afterSave(): void
    {
        $this->persistGuardedFields();
    }

    protected function persistGuardedFields(): void
    {
        $values = Arr::only($this->data, $this->guardedFormFields());

        if (! $values) {
            return;
        }

        $this->record->forceFill($values)->save();
    }
}
