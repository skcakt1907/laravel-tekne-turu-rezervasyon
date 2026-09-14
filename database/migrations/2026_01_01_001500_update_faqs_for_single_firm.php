<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SSS METİNLERİNİ TEK FİRMA MODELİNE ÇEK.
 *
 * İçerik veritabanında durduğu için seeder'ı güncellemek canlıyı
 * düzeltmiyor — yayındaki kayıtların da değişmesi gerekiyor.
 *
 * İki şey yanlıştı: ödeme sorusu "tur sahibine iletilir" diyordu (artık
 * tur sahibi yok), fiyat sorusu "tutar tahminidir" diyordu (fiyatlar
 * sabit). Bir de yalnızca tur sahiplerine görünen "Turumu nasıl
 * yayınlarım?" kaydı kaldı; o akış tamamen kaldırıldı.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('faqs')) {
            return;
        }

        $guncellenecek = [
            'Rezervasyon için ödeme yapmam gerekiyor mu?' => [
                'tr' => 'Hayır. Site üzerinden ödeme alınmaz. Rezervasyonunuzu yaparsınız, ödemeyi tur günü doğrudan teknede yaparsınız.',
                'en' => 'No. No payment is taken on the site. You book online and pay directly on board on the day of the tour.',
            ],
            'Fiyatlar kesin mi?' => [
                'tr' => 'Evet. Sitede gördüğünüz fiyat sabittir, değişmez.',
                'en' => 'Yes. The price shown on the site is fixed and never changes.',
            ],
        ];

        /*
         * Eslestirme PHP tarafinda: JSON_EXTRACT MySQL'e ozgu, testler
         * SQLite uzerinde kosuyor. Kayit sayisi bir elin parmagi kadar,
         * hepsini cekip karsilastirmak sorun degil.
         */
        foreach (DB::table('faqs')->get(['id', 'question']) as $kayit) {
            $soru = json_decode((string) $kayit->question, true)['tr'] ?? null;

            if ($soru !== null && isset($guncellenecek[$soru])) {
                DB::table('faqs')->where('id', $kayit->id)->update([
                    'answer' => json_encode($guncellenecek[$soru], JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            }
        }

        // Tur sahibi akışı kalktı; o kitleye yazılmış kayıtların karşılığı yok.
        DB::table('faqs')->where('audience', 'owner')->delete();
    }

    /**
     * Geri alınamaz: eski metinler tek firma modelinde yanlış bilgi
     * veriyordu, geri yazmanın faydası yok.
     */
    public function down(): void
    {
        //
    }
};
