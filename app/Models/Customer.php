<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Müşteri — üyelikten bağımsız CRM kaydı.
 *
 * Sitede üyelik yok; müşteri rezervasyon formunu doldurduğunda bu kayıt
 * oluşur (ya da e-postasıyla mevcut kaydına bağlanır). Şifre/giriş alanı
 * bilerek yok — bu model hiçbir zaman kimlik doğrulamada kullanılmaz.
 */
class Customer extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'whatsapp', 'locale', 'admin_note'];

    /**
     * E-postaya göre bul, yoksa aç.
     *
     * E-posta küçük harfe çevrilir: "Ali@x.com" ile "ali@x.com" aynı kişidir,
     * aksi hâlde aynı müşteri için iki kayıt oluşur ve CRM'in birleştirme
     * amacı boşa çıkar.
     *
     * Mevcut kayıtta ad/telefon güncellenir — insanlar numara değiştiriyor,
     * en son verdiği bilgi geçerli sayılır. Boş gelen alan eskisini silmez.
     */
    public static function bulVeyaAc(array $veri): self
    {
        $email = mb_strtolower(trim((string) ($veri['email'] ?? '')));

        $musteri = static::firstOrNew(['email' => $email]);

        $musteri->name = $veri['name'] ?: ($musteri->name ?: $email);

        foreach (['phone', 'whatsapp', 'locale'] as $alan) {
            if (! empty($veri[$alan])) {
                $musteri->{$alan} = $veri[$alan];
            }
        }

        $musteri->save();

        return $musteri;
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }
}
