<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MEVCUT MÜŞTERİ VERİSİNİ `customers` TABLOSUNA TAŞI.
 *
 * Üyelik kalkıyor ama kimse kaybolmuyor. İki kaynak birleştiriliyor:
 *   1) rezervasyonlardaki müşteri bilgisi (üye olmadan gelenler dahil)
 *   2) `users` tablosundaki müşteri rolündeki üyeler (henüz rezervasyon
 *      yapmamış olsalar bile — CRM'de kayıtları dursun)
 *
 * Eşleştirme e-posta ile yapılır. Şifreler taşınmaz; bu tablo giriş için
 * kullanılmıyor.
 *
 * Canlıda veri var, bu yüzden tek yönlü ve tekrar çalıştırılabilir yazıldı:
 * ikinci kez çalışırsa yeni kayıt açmaz, sadece boşta kalanları bağlar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        // 1) Rezervasyonlardaki müşteriler
        DB::table('reservations')
            ->select('customer_email', 'customer_name', 'customer_phone', 'customer_whatsapp', 'customer_locale')
            ->whereNotNull('customer_email')->where('customer_email', '!=', '')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($r) => mb_strtolower(trim($r->customer_email)))
            ->each(function ($grup, $email) {
                // En SON rezervasyondaki bilgi en günceli sayılır
                $son = $grup->last();

                DB::table('customers')->updateOrInsert(
                    ['email' => $email],
                    [
                        'name'       => $son->customer_name ?: $email,
                        'phone'      => $son->customer_phone ?: null,
                        'whatsapp'   => $son->customer_whatsapp ?: null,
                        'locale'     => $son->customer_locale ?: 'tr',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });

        // 2) Rezervasyonu olmayan müşteri üyeler — kaydı kaybolmasın
        DB::table('users')->where('role', 'customer')
            ->whereNotNull('email')->where('email', '!=', '')
            ->orderBy('id')->get()
            ->each(function ($u) {
                $email = mb_strtolower(trim($u->email));

                if (DB::table('customers')->where('email', $email)->exists()) {
                    return;
                }

                DB::table('customers')->insert([
                    'name'       => $u->name ?: $email,
                    'email'      => $email,
                    'phone'      => $u->phone ?? null,
                    'locale'     => 'tr',
                    'created_at' => $u->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            });

        // 3) Rezervasyonları müşteriye bağla
        DB::table('reservations')->whereNull('customer_id')
            ->whereNotNull('customer_email')->where('customer_email', '!=', '')
            ->orderBy('id')->get(['id', 'customer_email'])
            ->each(function ($r) {
                $id = DB::table('customers')
                    ->where('email', mb_strtolower(trim($r->customer_email)))->value('id');

                if ($id) {
                    DB::table('reservations')->where('id', $r->id)->update(['customer_id' => $id]);
                }
            });

        // 4) Notları müşteriye bağla (eski bağ: customer_notes.user_id -> users.id)
        if (Schema::hasColumn('customer_notes', 'customer_id') && Schema::hasColumn('customer_notes', 'user_id')) {
            DB::table('customer_notes as n')
                ->join('users as u', 'u.id', '=', 'n.user_id')
                ->whereNull('n.customer_id')
                ->orderBy('n.id')
                ->get(['n.id as not_id', 'u.email as email'])
                ->each(function ($n) {
                    $id = DB::table('customers')
                        ->where('email', mb_strtolower(trim((string) $n->email)))->value('id');

                    if ($id) {
                        DB::table('customer_notes')->where('id', $n->not_id)->update(['customer_id' => $id]);
                    }
                });
        }
    }

    /**
     * Geri alınamaz: taşınan veri `customers` tablosuyla birlikte
     * önceki migration'ın down() metodunda zaten siliniyor.
     */
    public function down(): void
    {
        //
    }
};
