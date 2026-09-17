<?php

namespace App\Support;

/**
 * SİTEDE GÖRÜNEN WHATSAPP LİNKİ.
 *
 * Panelden girilen numara serbest metin: "0532 296 47 85", "+90 532 ...",
 * "0(532) 296-47-85" gibi yazılabiliyor. wa.me ise yalnızca rakam ve
 * ülke kodu kabul eder — "+", boşluk, parantez, tire girerse link açılmaz.
 *
 * Bu sınıf numarayı wa.me biçimine çevirir ve emin olamadığı durumda
 * NULL döner. Yanlış bir numaraya link vermektense hiç link vermemek
 * tercih edildi: müşteri "WhatsApp'tan yazdım, dönmediniz" der ve mesaj
 * bilinmeyen bir numaraya gitmiş olur.
 *
 * NOT: Bu, panelde ayrı bir alandır (whatsapp_display_number) ve mesaj
 * GÖNDERİMİYLE ilgisi yoktur; gönderim numarası .env içindedir. Alan boş
 * bırakılırsa site telefonuna düşülür — çoğu işletmede ikisi aynı numara.
 */
final class WhatsAppLinki
{
    /** Türkiye cep numarası: 5xx xxx xx xx → 10 hane */
    private const TR_HANE = 10;

    private const TR_KODU = '90';

    /**
     * Panelde girilen metinden wa.me numarasını üretir.
     *
     * @return string|null Yalnızca rakam (ör. 905322964785); anlaşılamazsa null
     */
    public static function numara(?string $ham): ?string
    {
        $ham = trim((string) $ham);

        if ($ham === '') {
            return null;
        }

        $artiVar = str_starts_with($ham, '+');
        $rakam   = preg_replace('/\D+/', '', $ham) ?? '';

        if ($rakam === '') {
            return null;
        }

        // 0532... → baştaki sıfır şehir içi gösterim, uluslararası biçimde yok
        if (strlen($rakam) === self::TR_HANE + 1 && str_starts_with($rakam, '0')) {
            return self::TR_KODU . substr($rakam, 1);
        }

        // 5322964785 → ülke kodu hiç yazılmamış
        if (strlen($rakam) === self::TR_HANE && str_starts_with($rakam, '5')) {
            return self::TR_KODU . $rakam;
        }

        // 905322964785 → zaten tam
        if (strlen($rakam) === self::TR_HANE + 2 && str_starts_with($rakam, self::TR_KODU)) {
            return $rakam;
        }

        /*
         * Yurt dışı numara: yalnızca kullanıcı başına + koyduysa kabul edilir.
         * + yoksa ülke kodunun yazılıp yazılmadığı bilinemez ve tahmin
         * yürütmek yanlış numaraya link üretir.
         */
        if ($artiVar && strlen($rakam) >= 8 && strlen($rakam) <= 15) {
            return $rakam;
        }

        return null;
    }

    /**
     * Tıklanabilir wa.me adresi.
     *
     * @param  string|null  $mesaj  Sohbet kutusuna hazır gelen metin
     */
    public static function url(?string $ham, ?string $mesaj = null): ?string
    {
        $numara = self::numara($ham);

        if ($numara === null) {
            return null;
        }

        $adres = 'https://wa.me/' . $numara;

        if ($mesaj !== null && trim($mesaj) !== '') {
            $adres .= '?text=' . rawurlencode(trim($mesaj));
        }

        return $adres;
    }

    /**
     * Sitede kullanılacak numara: WhatsApp alanı boşsa site telefonuna düşer.
     */
    public static function sitedeki(): ?string
    {
        return self::url(
            setting('whatsapp_display_number') ?: setting('site_phone')
        );
    }
}
