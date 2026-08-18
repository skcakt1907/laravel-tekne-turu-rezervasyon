<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API
    |--------------------------------------------------------------------------
    | Faz 4. Meta işletme doğrulaması tamamlanmadan doldurulamaz.
    | enabled=false iken tüm bildirimler yalnızca e-posta kanalından gider.
    */
    'enabled' => env('WHATSAPP_ENABLED', false),
    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),

    /*
    | Meta'ya onaylatılacak 8 şablon (hepsi Utility kategorisinde).
    | Anahtar = sistem içi ad, değer = Meta'daki şablon adı.
    */
    'templates' => [
        'request_received_customer' => 'talep_alindi_musteri',
        'request_new_owner' => 'yeni_talep_sahip',       // Onayla / Reddet butonlu
        'request_new_admin' => 'yeni_talep_admin',
        'approved_customer' => 'rezervasyon_onaylandi_musteri',
        'approved_owner' => 'rezervasyon_kesinlesti_sahip',
        'rejected_customer' => 'talep_karsilanamadi_musteri',
        'pending_reminder_owner' => 'bekleyen_talep_sahip',
        'trip_reminder_customer' => 'gidis_hatirlatma_musteri',
    ],

    /*
    | Rezervasyon otomasyonu: yat sahibi yanıt vermezse.
    */
    'reminder_hours' => (int) env('RESERVATION_REMINDER_HOURS', 4),
    'escalate_hours' => (int) env('RESERVATION_ESCALATE_HOURS', 12),
];
