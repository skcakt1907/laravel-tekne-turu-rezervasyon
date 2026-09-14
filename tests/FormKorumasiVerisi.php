<?php

namespace Tests;

use App\Support\FormKorumasi;
use Illuminate\Support\Facades\Crypt;

/**
 * Herkese acik formlar bot korumasiyla korunuyor; testlerin gercek bir
 * kullanici gibi davranmasi icin tuzak alani bos, zaman damgasini da
 * gecerli gondermesi gerekiyor.
 *
 * Korumanin kendi testleri IletisimSpamTest ve RezervasyonSpamTest'te.
 */
trait FormKorumasiVerisi
{
    /**
     * @return array<string, string>
     */
    protected function korumaAlanlari(int $saniyeOnce = 30): array
    {
        return [
            FormKorumasi::TUZAK => '',
            FormKorumasi::ZAMAN => Crypt::encryptString((string) (time() - $saniyeOnce)),
        ];
    }
}
