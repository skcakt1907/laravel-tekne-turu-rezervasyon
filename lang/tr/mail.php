<?php

return [
    'subjects' => [
        'request_received_customer' => 'Rezervasyonunuz alındı — :code',
                'request_new_admin' => '[Yeni talep] :yacht — :code',
        'approved_customer' => 'Rezervasyonunuz onaylandı — :code',
                'rejected_customer' => 'Rezervasyonunuz karşılanamadı — :code',
        'cancelled_customer' => 'Rezervasyonunuz iptal edildi — :code',
        'cancelled_admin' => 'Rezervasyon iptal edildi — :code',
                'cancel_requested_admin' => '[İptal talebi] :yacht — :code',
        'pending_reminder_admin' => 'Yanıtlanmamış rezervasyon — :code',
        'escalated_admin' => '[Müdahale gerekiyor] Yanıtsız talep — :code',
        'trip_reminder_customer' => 'Gidişinize 3 gün kaldı — :code',
    ],

    'password' => [
        'subject' => ':site — şifre sıfırlama',
        'intro' => 'Şifrenizi sıfırlamak için bir talep aldık.',
        'action' => 'Şifremi sıfırla',
        'expire' => 'Bu bağlantı :count dakika sonra geçersiz olur.',
        'ignore' => 'Bu talebi siz göndermediyseniz e-postayı yok sayabilirsiniz; hesabınızda hiçbir şey değişmez.',
    ],

    'common' => [
        'hello' => 'Merhaba :name,',
        'code' => 'Rezervasyon kodu',
        'yacht' => 'Tur',
        'dates' => 'Tarih',
        'guests' => 'Kişi',
        'estimate' => 'Tutar',
        'customer' => 'Müşteri',
        'phone' => 'Telefon',
        'email' => 'E-posta',
        'note' => 'Not',
        'estimate_notice' => 'Fiyatlar sabittir, değişmez.',
        'no_payment' => 'Sitemiz üzerinden ödeme alınmaz. Ödeme, tur günü doğrudan teknede yapılır.',
        'view_reservation' => 'Rezervasyonu görüntüle',
        'qr_hint' => 'Bu QR kodu göstererek rezervasyonunuzu hızlıca doğrulatabilirsiniz.',
        'respond' => 'Rezervasyonu yanıtla',
        'regards' => 'İyi yolculuklar,',
        'footer_auto' => 'Bu e-posta :site tarafından otomatik gönderilmiştir.',
    ],

    'request_received_customer' => [
        'intro' => 'Rezervasyonunuz bize ulaştı.',
        'body' => 'Teyit edip en kısa sürede size döneceğiz. Bu aşamada herhangi bir ödeme yapmanız gerekmiyor; ödemeyi tur günü teknede yapacaksınız.',
    ],
    'request_new_admin' => [
        'intro' => 'Sisteme yeni bir rezervasyon talebi düştü.',
        'body' => 'Rezervasyon 4 saat içinde yanıtlanmazsa hatırlatma gider, 12 saat sonra yönetim ekranında işaretlenir.',
    ],
    'approved_customer' => [
        'intro' => 'Rezervasyonunuz onaylandı.',
        'body' => 'Seçtiğiniz tarih sizin adınıza kapatıldı. Buluşma detayları için sizinle iletişime geçeceğiz. Ödemeyi tur günü doğrudan teknede yapacaksınız.',
    ],
    'rejected_customer' => [
        'intro' => 'Talebiniz maalesef karşılanamadı.',
        'body' => 'Başka tarihler veya farklı bir tur için yeniden rezervasyon yapabilirsiniz.',
    ],
    'cancelled_customer' => [
        'intro' => 'Rezervasyonunuz iptal edildi.',
        'body' => 'Dilerseniz aynı tur için yeniden rezervasyon yapabilirsiniz.',
    ],
    'cancelled_admin' => [
        'intro' => 'Bir rezervasyon iptal edildi.',
        'body' => 'İlgili tarih takviminizde yeniden satışa açıldı.',
    ],
    'cancel_requested_admin' => [
        'intro' => 'Bir rezervasyon için müşteri iptal talebi geldi.',
        'body' => 'Kararı yönetim panelinden verebilirsiniz.',
    ],
    'pending_reminder_admin' => [
        'intro' => 'Yanıtlanmayı bekleyen bir talebiniz var.',
        'body' => 'Müşteri yanıt bekliyor. Onaylamak veya reddetmek için aşağıdaki bağlantıyı kullanabilirsiniz.',
    ],
    'escalated_admin' => [
        'intro' => 'Bu rezervasyon 12 saattir yanıtlanmadı.',
        'body' => 'Yönetim panelinden talebi siz onaylayabilir veya reddedebilirsiniz.',
    ],
    'trip_reminder_customer' => [
        'intro' => 'Gidişinize 3 gün kaldı.',
        'body' => 'İyi bir yolculuk için hazırlıklarınızı tamamlamayı ve buluşma detaylarını bizimle teyit etmeyi unutmayın. Ödemeyi teknede yapacaksınız.',
    ],
];
