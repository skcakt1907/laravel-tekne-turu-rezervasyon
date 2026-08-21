<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\TemplateRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Meta Business Manager'a yüklenecek şablon tanımlarını basar.
 *
 * Şablonlar elle de girilebilir; bu komut metinlerin koddaki hâliyle
 * Meta'daki hâlinin ayrışmamasını sağlar (parametre sırası kayarsa
 * mesajlar yanlış bilgiyle gider).
 */
class WhatsAppTemplates extends Command
{
    protected $signature = 'whatsapp:templates
                            {--json : Meta API yükünü JSON olarak bas}
                            {--out= : JSON çıktısını dosyaya yaz}';

    protected $description = 'WhatsApp şablon tanımlarını listeler veya Meta yükü olarak dışa aktarır';

    public function handle(): int
    {
        $payloads = TemplateRegistry::metaPayloads();

        if ($path = $this->option('out')) {
            File::put($path, json_encode($payloads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->components->info("Yazıldı: {$path} (".count($payloads).' şablon)');

            return self::SUCCESS;
        }

        if ($this->option('json')) {
            $this->line(json_encode($payloads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $rows = [];

        foreach (TemplateRegistry::definitions() as $key => $definition) {
            $rows[] = [
                $key,
                $definition['name'],
                implode(', ', array_keys($definition['body'])),
                $definition['buttons'] ? implode(' / ', $definition['buttons']) : '—',
                substr_count($definition['body']['tr'], '{{'),
            ];
        }

        $this->table(
            ['Sistem anahtarı', 'Meta şablon adı', 'Diller', 'Butonlar', 'Parametre'],
            $rows
        );

        $this->newLine();
        $this->components->info('Tümü UTILITY kategorisinde. Meta yükü için: --json veya --out=templates.json');

        return self::SUCCESS;
    }
}
