<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * HERKESE ACIK FORMLAR ICIN BOT KORUMASI.
 *
 * CAPTCHA yok -- gercek musteriyi ugrastirmadan, form doldurmanin
 * "insan gibi" olup olmadigina bakiyoruz. Uc olcut birlikte calisiyor;
 * tek basina hicbiri yeterli degil:
 *
 *  1. TUZAK ALAN (honeypot): Ekranda gorunmeyen bir alan. Insan gormez,
 *     dolduramaz; formu otomatik dolduran bot her alani doldurdugu icin
 *     buraya da yazar.
 *
 *  2. SURE: Formun acildigi an sifreli bir alanda tasiniyor. Insan formu
 *     birkac saniyeden once gonderemez; bot aninda gonderir.
 *
 *  3. BAGLANTI SAYISI: Spam mesajlarin neredeyse tamami baglanti tasir.
 *     Gercek bir musteri mesajinda birden fazla baglanti nadirdir.
 *
 * Bot yakalandiginda kullaniciya HATA GOSTERILMEZ; form basariyla
 * gonderilmis gibi davranilir. Sebep: hata gosterilirse bot hangi
 * alanin ele verdigini deneyerek bulur. Sessizce atmak, yakalanma
 * yonteminin omrunu uzatir.
 */
class FormKorumasi
{
    /** Tuzak alanin adi -- masum gorunsun diye gercekci bir isim. */
    public const TUZAK = 'website';

    /** Formun acilma zamanini tasiyan alan. */
    public const ZAMAN = 'form_zamani';

    /** Insanin formu doldurmasi en az bu kadar surer (saniye). */
    private const EN_AZ_SURE = 4;

    /** Bu kadar eski bir form gecersiz (saniye) -- 12 saat. */
    private const EN_COK_SURE = 43200;

    /** Mesajda kabul edilen en fazla baglanti sayisi. */
    private const EN_COK_BAGLANTI = 1;

    /** Forma gomulecek sifreli zaman damgasi. */
    public static function zamanDamgasi(): string
    {
        return Crypt::encryptString((string) time());
    }

    /**
     * Gonderim bot mu?
     *
     * @param  string|null  $metin  Baglanti sayisina bakilacak serbest metin
     */
    public static function botMu(Request $request, ?string $metin = null): bool
    {
        $sebep = self::sebep($request, $metin);

        if ($sebep === null) {
            return false;
        }

        // Yakalananlari kaydediyoruz: koruma fazla sikiysa ve gercek
        // musteri eleniyorsa bunu ancak buradan gorebiliriz.
        Log::info('Form korumasi: gonderim engellendi', [
            'sebep' => $sebep,
            'ip' => $request->ip(),
            'yol' => $request->path(),
        ]);

        return true;
    }

    /** Engelleme sebebi; temizse null. */
    private static function sebep(Request $request, ?string $metin): ?string
    {
        if (filled($request->input(self::TUZAK))) {
            return 'tuzak alan dolduruldu';
        }

        $acilis = self::acilisZamani($request);

        if ($acilis === null) {
            return 'zaman damgasi yok veya bozuk';
        }

        $gecen = time() - $acilis;

        if ($gecen < self::EN_AZ_SURE) {
            return 'cok hizli gonderildi (' . $gecen . ' sn)';
        }

        if ($gecen > self::EN_COK_SURE) {
            return 'form cok eski (' . $gecen . ' sn)';
        }

        if ($metin !== null && self::baglantiSayisi($metin) > self::EN_COK_BAGLANTI) {
            return 'mesajda fazla baglanti';
        }

        return null;
    }

    private static function acilisZamani(Request $request): ?int
    {
        $deger = $request->input(self::ZAMAN);

        if (! is_string($deger) || $deger === '') {
            return null;
        }

        try {
            return (int) Crypt::decryptString($deger);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Metindeki baglanti sayisi.
     *
     * Sadece "http" aramak yetmiyor; spam sik sik "www." ya da
     * "site.com" gibi protokolsuz adres yaziyor.
     */
    private static function baglantiSayisi(string $metin): int
    {
        /*
         * Siralama onemli: once tam adresi tuketen kaliplar deneniyor.
         * Aksi halde "www.ornek.com" hem "www." hem ".com" kalibina
         * takilip TEK adres IKI baglanti sayiliyor ve tek adres yazan
         * gercek musteri eleniyordu.
         */
        $desen = '~(https?://\S+|www\.\S+|\b[a-z0-9-]+\.(com|net|org|info|biz|ru|xyz|top|online|site|shop)\b)~i';

        return preg_match_all($desen, $metin);
    }
}
