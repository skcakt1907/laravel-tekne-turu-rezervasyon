<?php

return [
    // Diller — TR varsayılan, EN ikinci. Altyapı üçüncü dile hazır.
    'locales' => [
        'tr' => ['name' => 'Türkçe', 'flag' => '🇹🇷', 'prefix' => ''],    // öneksiz
        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'prefix' => 'en'], // /en/
    ],

    // İlanlar admin onayından geçsin mi? (Açık konu #1 — tek ayarla kapatılabilir)
    'require_listing_approval' => true,

    // İlan yayına girmeden önce zorunlu minimum fotoğraf sayısı
    'min_photos' => 4,

    // Yat tipleri
    'types' => [
        'motoryat' => 'Motoryat',
        'gulet' => 'Gulet',
        'katamaran' => 'Katamaran',
        'yelkenli' => 'Yelkenli',
        'surat_teknesi' => 'Sürat Teknesi',
        'tekne' => 'Tekne',
    ],

    'currencies' => ['EUR' => '€', 'TRY' => '₺', 'USD' => '$'],

    // Varsayılan komisyon oranı (%) — admin panelinden değiştirilebilir
    'default_commission_rate' => 10,
];
