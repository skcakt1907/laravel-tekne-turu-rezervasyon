<?php

namespace App\Enums;

/**
 * Panel kullanicisinin rolu.
 *
 * Kullanimda olan tek rol: Admin. Owner ve Customer ESKI MODELDEN kalma,
 * arayuzde hicbir yerde gosterilmiyor ve yeni kayitta secilemiyor:
 *  - Owner: tur sahibi paneli kaldirildi.
 *  - Customer: uyelik kalkti, musteriler `customers` tablosunda.
 *
 * DEGERLER SILINMEDI cunku veritabaninda hala bu rolde eski satirlar
 * olabilir; enum'dan cikarilirsa o satiri okumak hata firlatir. Roller
 * ancak o kayitlar temizlendikten sonra kaldirilabilir.
 */
enum UserRole: string
{
    case Admin = 'admin';

    /** @deprecated Tur sahibi paneli kaldirildi */
    case Owner = 'owner';

    /** @deprecated Uyelik kaldirildi, musteriler `customers` tablosunda */
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Yönetici',
            self::Owner => 'Tur Sahibi (eski)',
            self::Customer => 'Müşteri (eski)',
        };
    }
}
