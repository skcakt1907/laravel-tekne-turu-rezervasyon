<?php

namespace App\Observers;

use App\Models\Yacht;
use Illuminate\Support\Facades\Cache;

/** Ilan degisince ana sayfa onbellegi bayatlamasin. */
class YachtObserver
{
    public function saved(Yacht $yacht): void
    {
        $this->flush();
    }

    public function deleted(Yacht $yacht): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        foreach (array_keys(config('yacht.locales', ['tr' => []])) as $locale) {
            Cache::forget('home.'.$locale);
        }
    }
}
