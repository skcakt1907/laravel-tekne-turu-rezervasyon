<?php

return [
    'subjects' => [
        'request_received_customer' => 'Talebiniz alındı — :code',
        'request_new_owner' => 'Yeni rezervasyon talebi — :yacht (:code)',
        'request_new_admin' => '[Yeni talep] :yacht — :code',
        'approved_customer' => 'Rezervasyonunuz onaylandı — :code',
        'approved_owner' => 'Rezervasyon kesinleşti — :code',
        'rejected_customer' => 'Talebiniz karşılanamadı — :code',
        'cancelled_customer' => 'Rezervasyonunuz iptal edildi — :code',
        'cancelled_owner' => 'Rezervasyon iptal edildi — :code',
        'cancel_requested_owner' => 'Müşteri iptal talebi gönderdi — :code',
        'cancel_requested_admin' => '[İptal talebi] :yacht — :code',
        'pending_reminder_owner' => 'Yanıt bekleyen talebiniz var — :code',
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
        'estimate' => 'Tahmini tutar',
        'customer' => 'Müşteri',
        'phone' => 'Telefon',
        'email' => 'E-posta',
        'note' => 'Not',
        'estimate_notice' => 'Tahmini tutardır, kesin fiyat onay sırasında netleşir.',
        'no_payment' => 'Sitemiz üzerinden ödeme alınmaz. Ödeme, onaydan sonra doğrudan tur sahibiyle yapılır.',
        'view_reservation' => 'Rezervasyonu görüntüle',
        'qr_hint' => 'Bu QR kodu göstererek rezervasyonunuzu hızlıca doğrulatabilirsiniz.',
        'respond' => 'Talebi yanıtla',
        'regards' => 'İyi yolculuklar,',
        'footer_auto' => 'Bu e-posta :site tarafından otomatik gönderilmiştir.',
    ],

    'request_received_customer' => [
        'intro' => 'Rezervasyon talebiniz bize ulaştı ve tur sahibine iletildi.',
        'body' => 'Tur sahibi talebinizi değerlendirip onaylayınca size tekrar yazacağız. Bu aşamada herhangi bir ödeme yapmanız gerekmiyor ve takvimde tarih henüz kapanmadı.',
    ],
    'request_new_owner' => [
        'intro' => 'Turunuz için yeni bir rezervasyon talebi var.',
        'body' => 'Aşağıdaki bağlantıdan panele girmeden onaylayabilir veya reddedebilirsiniz. Tarih yalnızca siz onayladığınızda kapanır.',
    ],
    'request_new_admin' => [
        'intro' => 'Sisteme yeni bir rezervasyon talebi düştü.',
        'body' => 'Tur sahibi 4 saat içinde yanıt vermezse hatırlatma gider, 12 saat sonra talep yönetim ekranında işaretlenir.',
    ],
    'approved_customer' => [
        'intro' => 'Rezervasyonunuz onaylandı.',
        'body' => 'Seçtiğiniz tarih sizin adınıza kapatıldı. Ödeme ve teslim detayları için tur sahibi sizinle iletişime geçecek.',
    ],
    'approved_owner' => [
        'intro' => 'Rezervasyon kesinleşti, tarih takviminizde kapatıldı.',
        'body' => 'Müşteri iletişim bilgileri aşağıdadır.',
    ],
    'rejected_customer' => [
        'intro' => 'Talebiniz maalesef karşılanamadı.',
        'body' => 'Başka tarihler veya farklı bir tur için yeniden talep gönderebilirsiniz.',
    ],
    'cancelled_customer' => [
        'intro' => 'Rezervasyonunuz iptal edildi.',
        'body' => 'Dilerseniz aynı tur için yeni bir talep gönderebilirsiniz.',
    ],
    'cancelled_owner' => [
        'intro' => 'Bir rezervasyon iptal edildi.',
        'body' => 'İlgili tarih takviminizde yeniden satışa açıldı.',
    ],
    'cancel_requested_owner' => [
        'intro' => 'Müşteri bu rezervasyon için iptal talebinde bulundu.',
        'body' => 'Kararı siz veriyorsunuz. Kabul ederseniz panelden "İptal et" işlemini uygulayın; tarih tekrar satışa açılır.',
    ],
    'cancel_requested_admin' => [
        'intro' => 'Bir rezervasyon için müşteri iptal talebi geldi.',
        'body' => 'Tur sahibi karar vermezse yönetim panelinden siz de iptal edebilirsiniz.',
    ],
    'pending_reminder_owner' => [
        'intro' => 'Yanıtlanmayı bekleyen bir talebiniz var.',
        'body' => 'Müşteriler hızlı dönüş yapılan ilanları tercih ediyor. Talebi onaylamak veya reddetmek için aşağıdaki bağlantıyı kullanabilirsiniz.',
    ],
    'escalated_admin' => [
        'intro' => 'Tur sahibi bu talebi 12 saattir yanıtlamadı.',
        'body' => 'Yönetim panelinden talebi siz onaylayabilir veya reddedebilirsiniz.',
    ],
    'trip_reminder_customer' => [
        'intro' => 'Gidişinize 3 gün kaldı.',
        'body' => 'İyi bir yolculuk için hazırlıklarınızı tamamlamayı ve tur sahibiyle buluşma detaylarını teyit etmeyi unutmayın.',
    ],
];
