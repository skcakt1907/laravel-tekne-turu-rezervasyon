<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Yasal sayfa metinleri — ŞABLONDUR, avukat/müşteri onayından geçmelidir.
 * Yer tutucular ({{firma}} gibi) yayın öncesi gerçek bilgilerle doldurulmalı.
 */
class LegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => $page) {
            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $page['title'],
                    'body' => $page['body'],
                    'seo_title' => $page['title'],
                    'seo_description' => $page['seo'] ?? null,
                    'sort' => $page['sort'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function pages(): array
    {
        $company = '{{FİRMA UNVANI}}';

        return [
            'hakkimizda' => [
                'sort' => 0,
                'title' => ['tr' => 'Hakkımızda', 'en' => 'About Us'],
                'seo' => [
                    'tr' => 'Yat sahibinden doğrudan kiralama platformu.',
                    'en' => 'Charter directly from yacht owners.',
                ],
                'body' => [
                    'tr' => '<p>Türkiye kıyılarındaki yatları, sahipleriyle doğrudan buluşturan bir kiralama platformuyuz.
                        Sitemizde ödeme alınmaz; talebiniz yat sahibine iletilir, onay verdiğinde tarih size ayrılır ve
                        ödemeyi doğrudan yat sahibiyle yaparsınız.</p>
                        <p>Amacımız aracı katmanını inceltmek: yat sahibi kendi fiyatını ve takvimini yönetir, misafir
                        gerçek fiyatı görür, biz de yalnızca gerçekleşen kiralamalardan komisyon alırız.</p>',
                    'en' => '<p>We connect guests directly with yacht owners along the Turkish coast. No payment is taken
                        on this site; your request goes to the owner, and once approved the dates are held for you while
                        payment is arranged directly with the owner.</p>',
                ],
            ],

            'kullanim-kosullari' => [
                'sort' => 1,
                'title' => ['tr' => 'Kullanım Koşulları', 'en' => 'Terms of Use'],
                'body' => [
                    'tr' => "<p><strong>Bu metin şablondur; yayına almadan önce hukuk danışmanınıza onaylatın.</strong></p>
                        <h3>1. Taraflar ve konu</h3>
                        <p>Bu site {$company} tarafından işletilmektedir. Site, yat sahipleri ile kiralamak isteyen
                        kullanıcıları buluşturan bir <em>ilan ve talep</em> platformudur.</p>
                        <h3>2. Platformun rolü</h3>
                        <p>Platform, kiralama sözleşmesinin tarafı değildir. Kiralama ilişkisi doğrudan yat sahibi ile
                        misafir arasında kurulur. Site üzerinden ödeme alınmaz; ücret, taraflar arasında ödenir.</p>
                        <h3>3. Fiyatlar</h3>
                        <p>Sitede gösterilen tutarlar <em>tahminidir</em>. Kesin fiyat, yat sahibinin onayı sırasında
                        netleşir. Yakıt, temizlik, mürettebat gibi ek kalemler ilanda belirtildiği şekilde uygulanır.</p>
                        <h3>4. Rezervasyon ve onay</h3>
                        <p>Gönderilen talep tek başına rezervasyon oluşturmaz. Tarih, yalnızca yat sahibi onay verdiğinde
                        ayrılır. Onaylanan rezervasyonun iptali için iptal politikası geçerlidir.</p>
                        <h3>5. Kullanıcı yükümlülükleri</h3>
                        <p>Kullanıcı, verdiği bilgilerin doğru olduğunu kabul eder. Yat sahibi, ilanındaki bilgilerin ve
                        yasal izinlerin (denize elverişlilik, sigorta, kaptan belgeleri) doğruluğundan sorumludur.</p>
                        <h3>6. Sorumluluğun sınırı</h3>
                        <p>Platform, kiralamanın gerçekleşmemesinden, hizmet kalitesinden veya taraflar arasındaki
                        uyuşmazlıklardan sorumlu tutulamaz.</p>
                        <h3>7. Yürürlük</h3>
                        <p>Siteyi kullanmakla bu koşulları kabul etmiş sayılırsınız. Koşullar güncellenebilir; güncel
                        metin bu sayfada yayımlanır.</p>",
                    'en' => "<p><strong>This is a template; have it reviewed by your legal counsel before publishing.</strong></p>
                        <p>This site is operated by {$company} and acts as a listing and request platform connecting
                        yacht owners with guests. The platform is not a party to the charter agreement, takes no payment,
                        and displays estimated prices that are finalised upon the owner's approval.</p>",
                ],
            ],

            'kvkk' => [
                'sort' => 2,
                'title' => ['tr' => 'KVKK Aydınlatma Metni', 'en' => 'Privacy Notice'],
                'body' => [
                    'tr' => "<p><strong>Bu metin şablondur; veri sorumlusu bilgileri doldurulmalı ve hukuk onayından geçmelidir.</strong></p>
                        <h3>Veri sorumlusu</h3>
                        <p>{$company} (\"Platform\"), 6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında veri
                        sorumlusudur.</p>
                        <h3>İşlenen veriler</h3>
                        <p>Ad soyad, e-posta, telefon/WhatsApp numarası, rezervasyon talebine ilişkin bilgiler
                        (yat, tarih, kişi sayısı, notunuz), IP adresi ve onay kayıtları.</p>
                        <h3>İşleme amaçları</h3>
                        <p>Rezervasyon talebinizin yat sahibine iletilmesi, sürecin takibi, e-posta ve WhatsApp
                        bildirimlerinin gönderilmesi, yasal yükümlülüklerin yerine getirilmesi ve uyuşmazlık hâlinde
                        ispat.</p>
                        <h3>Hukuki sebep</h3>
                        <p>Sözleşmenin kurulması ve ifası, meşru menfaat ve — WhatsApp bildirimleri bakımından —
                        <em>açık rızanız</em>. Açık rıza kaydınız tarih ve IP bilgisiyle saklanır.</p>
                        <h3>Aktarım</h3>
                        <p>Talebiniz, ilgili yat sahibiyle paylaşılır. Bildirim gönderimi için e-posta servis
                        sağlayıcısı ve WhatsApp altyapısı (Meta) kullanılır.</p>
                        <h3>Saklama süresi</h3>
                        <p>Veriler, ilişkinin sona ermesinden itibaren yasal zamanaşımı süreleri boyunca saklanır,
                        sonrasında silinir veya anonimleştirilir.</p>
                        <h3>Haklarınız</h3>
                        <p>KVKK m.11 kapsamında verilerinize erişme, düzeltme, silme ve işlemeye itiraz haklarına
                        sahipsiniz. Başvurularınızı iletişim sayfamızdaki adrese iletebilirsiniz.</p>",
                    'en' => '<p><strong>Template text; complete the controller details and obtain legal review.</strong></p>
                        <p>We process your name, contact details and booking request data to pass your request to the
                        yacht owner and to send you notifications. WhatsApp notifications are sent only with your
                        explicit consent, which we record with a timestamp and IP address. You may request access,
                        correction or deletion of your data at any time.</p>',
                ],
            ],

            'gizlilik' => [
                'sort' => 3,
                'title' => ['tr' => 'Gizlilik Politikası', 'en' => 'Privacy Policy'],
                'body' => [
                    'tr' => '<p>Kişisel verilerinizi yalnızca rezervasyon sürecinin yürütülmesi için işleriz.
                        Verileriniz pazarlama amacıyla üçüncü kişilere satılmaz veya kiralanmaz.</p>
                        <p>Site üzerinden ödeme alınmadığı için kart bilgisi toplanmaz ve saklanmaz.</p>
                        <p>Ayrıntılar için <em>KVKK Aydınlatma Metni</em> sayfamıza bakınız.</p>',
                    'en' => '<p>We process your personal data solely to run the booking process. We never sell or rent
                        your data. As no payment is taken on this site, no card details are collected or stored.</p>',
                ],
            ],

            'cerez-politikasi' => [
                'sort' => 4,
                'title' => ['tr' => 'Çerez Politikası', 'en' => 'Cookie Policy'],
                'body' => [
                    'tr' => '<p>Sitemizde iki tür çerez kullanılır:</p>
                        <ul>
                            <li><strong>Zorunlu çerezler:</strong> Oturumunuzun açık kalması ve form güvenliği (CSRF)
                            için gereklidir; devre dışı bırakılamaz.</li>
                            <li><strong>Analitik çerezler:</strong> Yalnızca onayınızla çalışır. Onay vermezseniz hiçbir
                            ölçüm betiği yüklenmez.</li>
                        </ul>
                        <p>Tercihiniz tarayıcınızda saklanır ve dilediğiniz zaman tarayıcı verilerini temizleyerek
                        değiştirebilirsiniz.</p>',
                    'en' => '<p>We use strictly necessary cookies (session and CSRF protection) and, only with your
                        consent, analytics cookies. If you decline, no measurement script is loaded. Your preference is
                        stored in your browser.</p>',
                ],
            ],

            'iptal-politikasi' => [
                'sort' => 5,
                'title' => ['tr' => 'İptal Politikası', 'en' => 'Cancellation Policy'],
                'body' => [
                    'tr' => '<p><strong>Bu metin şablondur; müşteri onayı gerekir.</strong></p>
                        <p>Sitemizde ön ödeme alınmadığı için iptal işlemi ücretsizdir.</p>
                        <h3>Talep aşaması</h3>
                        <p>Henüz onaylanmamış bir talep, herhangi bir yükümlülük doğurmaz.</p>
                        <h3>Onaylanmış rezervasyon</h3>
                        <p>İptal talebinizi rezervasyon sayfanızdan iletebilirsiniz. Talebiniz yat sahibine bildirilir;
                        iptal kararı yat sahibindedir. Onaylanması hâlinde tarih tekrar satışa açılır.</p>
                        <h3>Yat sahibi kaynaklı iptaller</h3>
                        <p>Yat sahibi, hava koşulları veya teknik zorunluluk gibi sebeplerle rezervasyonu iptal
                        edebilir; bu durumda size bildirim gönderilir.</p>
                        <h3>Gelmeme (no-show)</h3>
                        <p>Bildirimde bulunmadan gelinmeyen rezervasyonlar kayıtlarımıza işlenir; tekrarlayan
                        durumlarda talepleriniz sınırlandırılabilir.</p>',
                    'en' => '<p><strong>Template text; requires client approval.</strong></p>
                        <p>As no prepayment is taken, cancellation is free of charge. Unapproved requests carry no
                        obligation. For an approved booking you may send a cancellation request from your booking page;
                        the decision rests with the owner. Repeated no-shows may lead to restrictions.</p>',
                ],
            ],
        ];
    }
}
