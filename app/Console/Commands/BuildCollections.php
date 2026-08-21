<?php

namespace App\Console\Commands;

use App\Services\CollectionService;
use Illuminate\Console\Command;

/**
 * Aylik hakedis dokumunu uretir. Varsayilan: kapanmis onceki ay.
 * Zamanlayici her ayin 1'inde calistirir.
 */
class BuildCollections extends Command
{
    protected $signature = 'collections:build {--period= : YYYY-MM biciminde donem}';

    protected $description = 'Aylik tahsilat dokumunu olusturur veya gunceller';

    public function handle(CollectionService $collections): int
    {
        if ($period = $this->option('period')) {
            if (! preg_match('/^(\d{4})-(\d{2})$/', $period, $m)) {
                $this->components->error('Donem YYYY-MM biciminde olmali.');

                return self::FAILURE;
            }

            [$year, $month] = [(int) $m[1], (int) $m[2]];
        } else {
            [$year, $month] = $collections->previousPeriod();
        }

        $built = $collections->build($year, $month);

        $this->components->info(sprintf(
            '%02d/%d donemi: %d yat sahibi, toplam komisyon %s',
            $month,
            $year,
            $built->count(),
            number_format((float) $built->sum('commission'), 2, ',', '.')
        ));

        return self::SUCCESS;
    }
}
