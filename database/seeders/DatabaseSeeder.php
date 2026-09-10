<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\YachtStatus;
use App\Models\CommissionSetting;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\Setting;
use App\Models\User;
use App\Models\Yacht;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->users();
        $this->settings();
        $this->features();
        $this->content();
        $this->demoYacht();
        $this->tours();
    }

    private function users(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@yatkiralama.com'],
            ['name' => 'Sistem Yöneticisi', 'password' => Hash::make('admin123'), 'phone' => '+905000000000']
        );
        $admin->forceFill(['role' => UserRole::Admin, 'is_approved' => true, 'email_verified_at' => now()])->save();

        $owner = User::updateOrCreate(
            ['email' => 'sahip@yatkiralama.com'],
            [
                'name' => 'Demo Tur Sahibi',
                'password' => Hash::make('sahip123'),
                'phone' => '+905001112233',
                'whatsapp_no' => '+905001112233',
                'company_name' => 'Demo Marin Turizm Ltd. Sti.',
            ]
        );
        $owner->forceFill(['role' => UserRole::Owner, 'is_approved' => true, 'email_verified_at' => now()])->save();
    }

    private function settings(): void
    {
        $defaults = [
            'site_name' => 'Marmaris Tekne Turları',
            'site_email' => 'info@yatkiralama.com',
            'site_phone' => '+90 500 000 00 00',
            'whatsapp_display_number' => '+90 500 000 00 00',
            'address' => '',
            'default_currency' => 'EUR',
            'estimate_notice_tr' => 'Tahmini tutardır, kesin fiyat onay sırasında netleşir.',
            'estimate_notice_en' => 'This is an estimate; the final price is confirmed upon approval.',
            'site_description' => 'Marmaris\'te günübirlik tekne turları. Ödeme yapmadan rezervasyon talebi gönderin.',
            'google_analytics_id' => '',
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => 'general']);
        }
        Setting::flush();

        CommissionSetting::updateOrCreate(
            ['scope' => CommissionSetting::SCOPE_GLOBAL, 'target_id' => null],
            ['rate' => config('yacht.default_commission_rate', 10), 'note' => 'Varsayılan genel oran']
        );
    }

    private function features(): void
    {
        $groups = [
            'konfor' => [
                ['Klima', 'Air conditioning'],
                ['Jakuzi', 'Jacuzzi'],
                ['Wi-Fi', 'Wi-Fi'],
                ['Müzik sistemi', 'Sound system'],
                ['Güneşlenme alanı', 'Sun deck'],
            ],
            'mutfak' => [
                ['Tam donanımlı mutfak', 'Full galley'],
                ['Buzdolabı', 'Refrigerator'],
                ['Barbekü', 'Barbecue'],
                ['Aşçı', 'Chef'],
            ],
            'su_sporlari' => [
                ['Deniz kayağı', 'Water ski'],
                ['Dalış ekipmanı', 'Diving equipment'],
                ['Kano', 'Canoe'],
                ['Jet ski', 'Jet ski'],
                ['Şnorkel', 'Snorkel'],
            ],
            'guvenlik' => [
                ['Can yeleği', 'Life jackets'],
                ['İlk yardım seti', 'First aid kit'],
                ['Cankurtaran salı', 'Life raft'],
            ],
            'teknik' => [
                ['Jeneratör', 'Generator'],
                ['Su yapıcı', 'Watermaker'],
                ['Stabilizatör', 'Stabilizer'],
            ],
        ];

        $sort = 0;
        foreach ($groups as $group => $items) {
            foreach ($items as [$tr, $en]) {
                Feature::updateOrCreate(
                    ['slug' => Str::slug($en)],
                    ['name' => ['tr' => $tr, 'en' => $en], 'group' => $group, 'sort' => $sort++]
                );
            }
        }
    }

    private function content(): void
    {
        $this->call(LegalPagesSeeder::class);

        $faqs = [
            [
                ['tr' => 'Rezervasyon için ödeme yapmam gerekiyor mu?', 'en' => 'Do I need to pay to book?'],
                ['tr' => 'Hayır. Site üzerinden ödeme alınmaz; talebiniz tur sahibine iletilir, onaylandıktan sonra ödeme taraflar arasında yapılır.', 'en' => 'No. No payment is taken on the site.'],
                'customer',
            ],
            [
                ['tr' => 'Fiyatlar kesin mi?', 'en' => 'Are the prices final?'],
                ['tr' => 'Sitede gördüğünüz tutar tahminidir; kesin fiyat onay sırasında netleşir.', 'en' => 'The amount shown is an estimate.'],
                'customer',
            ],
            [
                ['tr' => 'Turumu nasıl yayınlarım?', 'en' => 'How do I list my tour?'],
                ['tr' => 'Tur sahibi panelinden kayıt olun, ilanınızı girin. Admin onayından sonra yayına alınır.', 'en' => 'Register in the owner panel and submit your listing.'],
                'owner',
            ],
        ];

        foreach ($faqs as $i => [$q, $a, $audience]) {
            Faq::updateOrCreate(
                ['question->tr' => $q['tr']],
                ['question' => $q, 'answer' => $a, 'audience' => $audience, 'sort' => $i]
            );
        }
    }

    /** Test paketinin dayandigi yayinda/fiyatli/kapasiteli sabit demo tur. */
    private function demoYacht(): void
    {
        $owner = User::where('email', 'sahip@yatkiralama.com')->first();

        if (! $owner) {
            return;
        }

        $yachts = [
            [
                'slug' => 'demo-gulet-mavi-ruzgar',
                'name' => ['tr' => 'Mavi Rüzgar', 'en' => 'Mavi Rüzgar'],
                'type' => 'gulet',
                'length_m' => 28.5, 'cabins' => 6, 'beds' => 12, 'wc' => 6,
                'capacity' => 16, 'sleep_capacity' => 12, 'build_year' => 2016,
                'price' => 2400, 'price_child' => 1200,
            ],
            [
                'slug' => 'demo-motoryat-deniz-yildizi',
                'name' => ['tr' => 'Deniz Yıldızı', 'en' => 'Deniz Yıldızı'],
                'type' => 'motoryat',
                'length_m' => 18.0, 'cabins' => 3, 'beds' => 6, 'wc' => 3,
                'capacity' => 12, 'sleep_capacity' => 6, 'build_year' => 2020,
                'price' => 350, 'price_child' => 175,
            ],
        ];

        foreach ($yachts as $data) {
            $price = $data['price'];
            $priceChild = $data['price_child'];
            unset($data['price'], $data['price_child']);

            $yacht = Yacht::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'owner_id' => $owner->id,
                    'description' => ['tr' => 'Demo ilan — gerçek içerik müşteriden gelecek.', 'en' => 'Demo listing.'],
                    'with_crew' => true,
                    'currency' => 'EUR',
                    'unit_daily' => true,
                    'day_start' => '09:00',
                    'day_end' => '18:00',
                    'is_open' => true,
                ])
            );

            $yacht->forceFill(['status' => YachtStatus::Published, 'published_at' => now()])->save();

            $yacht->rates()->updateOrCreate(
                ['unit' => 'day', 'season_start' => null, 'season_end' => null],
                ['price' => $price, 'price_child' => $priceChild, 'min_duration' => 1, 'label' => 'Temel fiyat']
            );

            $yacht->features()->sync(Feature::inRandomOrder()->limit(8)->pluck('id'));
        }
    }

    /**
     * Musterinin gercek 5 turu + fotograflari (public/uploads/yachts/<slug>).
     * Fiyat ve kapasite BILEREK bos: musteri admin panelden girecek.
     * Aciklama metinleri taslaktir, musteri onayindan gecmeli.
     */
    private function tours(): void
    {
        $owner = User::where('email', 'sahip@yatkiralama.com')->first();

        if (! $owner) {
            return;
        }

        // Eski (birlestirilen/kaldirilan) ilanlar
        Yacht::whereIn('slug', [
            'davy-jones-alkollu-tekne-turu',
            'davy-jones-alkolsuz-tekne-turu',
            'soft-tekne-turu',
        ])->each(fn (Yacht $y) => $y->reservations()->doesntExist() ? $y->forceDelete() : null);

        $tours = [
            [
                'slug' => 'dalis-turu',
                'cover' => 'dalis-turu-01.jpg',
                'name' => ['tr' => 'Dalış Turu', 'en' => 'Diving Tour'],
                'description' => [
                    'tr' => '<p>Marmaris koylarında günübirlik dalış turu. Deneyimli eğitmenler eşliğinde, daha önce hiç dalmamış olanlar da dalabilir; ekipman ve eğitim dahildir.</p><p>Öğle yemeği ve içecekler teknede servis edilir.</p>',
                    'en' => '<p>Full-day diving tour around the bays of Marmaris. Accompanied by experienced instructors — first-time divers are welcome; equipment and training are included.</p><p>Lunch and drinks are served on board.</p>',
                ],
            ],
            [
                'slug' => 'davy-jones-korsan-tekne-turu',
                'cover' => 'davy-jones-korsan-tekne-turu-01.jpg',
                'name' => ['tr' => 'Davy Jones Korsan Tekne Turu', 'en' => 'Davy Jones Pirate Boat Tour'],
                'description' => [
                    'tr' => '<p>Korsan temalı Davy Jones teknesiyle günübirlik Marmaris turu. Müzik, köpük partisi ve yüzme molalarıyla hareketli bir gün.</p><p>Her şey dahil: öğle yemeği ve içecekler fiyata dahildir.</p>',
                    'en' => '<p>A full-day Marmaris cruise aboard the pirate-themed Davy Jones boat. Music, foam party and swimming stops make for a lively day.</p><p>All-inclusive: lunch and drinks are covered.</p>',
                ],
            ],
            [
                'slug' => 'hersey-dahil-tekne-turu',
                'cover' => 'hersey-dahil-tekne-turu-01.jpg',
                'name' => ['tr' => 'Her Şey Dahil Tekne Turu', 'en' => 'All-Inclusive Boat Tour'],
                'description' => [
                    'tr' => '<p>Marmaris\'in koylarında sakin, aile dostu günübirlik tekne turu. Berrak sularda yüzme molaları ve güneşlenme güvertesi.</p><p>Her şey dahil: öğle yemeği ve içecekler fiyata dahildir.</p>',
                    'en' => '<p>A relaxed, family-friendly day cruise around the bays of Marmaris, with swimming stops in crystal-clear water and a sun deck.</p><p>All-inclusive: lunch and drinks are covered.</p>',
                ],
            ],
            [
                'slug' => 'kleopatra-adasi-tekne-turu',
                'cover' => 'kleopatra-adasi-tekne-turu-01.jpg',
                'name' => ['tr' => 'Kleopatra Adası Tekne Turu', 'en' => 'Cleopatra Island Boat Tour'],
                'description' => [
                    'tr' => '<p>Ünlü kumuyla bilinen Kleopatra (Sedir) Adası\'na günübirlik tekne turu. Adada yüzme ve antik kalıntıları gezme fırsatı.</p><p>Her şey dahil: öğle yemeği ve içecekler fiyata dahildir.</p>',
                    'en' => '<p>A day trip to Cleopatra (Sedir) Island, famous for its unique sand. Time to swim and to walk among the ancient ruins on the island.</p><p>All-inclusive: lunch and drinks are covered.</p>',
                ],
            ],
            [
                'slug' => 'dalyan-tekne-turu',
                'cover' => 'dalyan-tekne-turu-07.jpg',
                'name' => ['tr' => 'Dalyan Tekne Turu', 'en' => 'Dalyan Boat Tour'],
                'description' => [
                    'tr' => '<p>Marmaris çıkışlı Dalyan turu: kaya mezarları, İztuzu (Caretta) Plajı ve çamur banyoları.</p><p>Her şey dahil: öğle yemeği ve içecekler fiyata dahildir.</p>',
                    'en' => '<p>Dalyan tour departing from Marmaris: the rock tombs, İztuzu (Turtle) Beach and the mud baths.</p><p>All-inclusive: lunch and drinks are covered.</p>',
                ],
            ],
        ];

        foreach ($tours as $i => $data) {
            $cover = $data['cover'];
            unset($data['cover']);

            $tour = Yacht::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'owner_id' => $owner->id,
                    'type' => 'tekne',
                    'with_crew' => true,
                    'currency' => 'EUR',
                    'unit_daily' => true,
                    'is_open' => true,
                ])
            );

            $tour->forceFill([
                'status' => YachtStatus::Published,
                'published_at' => $tour->published_at ?? now(),
                'is_featured' => $i < 3,
            ])->save();

            $this->tourPhotos($tour, $cover);
        }
    }

    /**
     * public/uploads/yachts/<slug> altindaki medyayi galeriye baglar.
     * Videolarin kapak karesi ayni adda .jpg dosyasidir (dalis-turu-v01.mp4
     * -> dalis-turu-v01.jpg); o jpg galeride ayri bir foto olarak listelenmez.
     */
    private function tourPhotos(Yacht $tour, string $cover): void
    {
        $dir = public_path("uploads/yachts/{$tour->slug}");

        if (! is_dir($dir)) {
            return;
        }

        $videos = collect(glob("{$dir}/*.mp4"))
            ->map(fn (string $path) => basename($path))
            ->sort(SORT_NATURAL)
            ->values();

        $posters = $videos->map(fn (string $v) => substr($v, 0, -4).'.jpg');

        $files = collect(glob("{$dir}/*.jpg"))
            ->map(fn (string $path) => basename($path))
            ->reject(fn (string $f) => $posters->contains($f)) // video kapak kareleri
            ->sort(SORT_NATURAL)
            ->values();

        // Kapak once gelsin, videolar en sona
        $files = $files->reject(fn (string $f) => $f === $cover)->prepend($cover)->concat($videos);

        foreach ($files as $sort => $file) {
            $isVideo = str_ends_with($file, '.mp4');
            $poster = $isVideo ? substr($file, 0, -4).'.jpg' : null;

            $tour->photos()->updateOrCreate(
                ['path' => "yachts/{$tour->slug}/{$file}"],
                [
                    'poster' => $poster && is_file("{$dir}/{$poster}") ? "yachts/{$tour->slug}/{$poster}" : null,
                    'alt' => ['tr' => $tour->getTranslation('name', 'tr'), 'en' => $tour->getTranslation('name', 'en')],
                    'sort' => $sort,
                    'is_cover' => $file === $cover,
                ]
            );
        }
    }
}
