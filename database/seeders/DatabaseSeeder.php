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
                'units' => ['day' => 2400, 'week' => 14000],
            ],
            [
                'slug' => 'demo-motoryat-deniz-yildizi',
                'name' => ['tr' => 'Deniz Yıldızı', 'en' => 'Deniz Yıldızı'],
                'type' => 'motoryat',
                'length_m' => 18.0, 'cabins' => 3, 'beds' => 6, 'wc' => 3,
                'capacity' => 12, 'sleep_capacity' => 6, 'build_year' => 2020,
                'units' => ['hour' => 350, 'day' => 1800],
            ],
        ];

        foreach ($yachts as $data) {
            $units = $data['units'];
            unset($data['units']);

            $yacht = Yacht::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'owner_id' => $owner->id,
                    'description' => ['tr' => 'Demo ilan — gerçek içerik müşteriden gelecek.', 'en' => 'Demo listing.'],
                    'with_crew' => true,
                    'currency' => 'EUR',
                    'turnaround_minutes' => 180,
                    'unit_hourly' => isset($units['hour']),
                    'unit_daily' => isset($units['day']),
                    'unit_weekly' => isset($units['week']),
                    'is_open' => true,
                ])
            );

            $yacht->forceFill(['status' => YachtStatus::Published, 'published_at' => now()])->save();

            foreach ($units as $unit => $price) {
                $yacht->rates()->updateOrCreate(
                    ['unit' => $unit, 'season_start' => null, 'season_end' => null],
                    ['price' => $price, 'min_duration' => $unit === 'hour' ? 3 : 1, 'label' => 'Temel fiyat']
                );
            }

            $yacht->features()->sync(Feature::inRandomOrder()->limit(8)->pluck('id'));
        }
    }

    /**
     * Gercek 4 tur ilani — taslak (Draft) olarak girilir. Fiyat, kapasite ve
     * fotograflar musteriden gelene kadar admin panelden doldurulur.
     */
    private function tours(): void
    {
        $owner = User::where('email', 'sahip@yatkiralama.com')->first();

        if (! $owner) {
            return;
        }

        $tours = [
            [
                'slug' => 'dalis-turu',
                'name' => ['tr' => 'Dalış Turu', 'en' => 'Diving Tour'],
                'description' => [
                    'tr' => 'Marmaris\'te günübirlik dalış turu — her şey dahil.',
                    'en' => 'Full-day diving tour in Marmaris — all-inclusive.',
                ],
            ],
            [
                'slug' => 'davy-jones-alkollu-tekne-turu',
                'name' => ['tr' => 'Davy Jones Alkollü Tekne Turu', 'en' => 'Davy Jones Alcoholic Boat Tour'],
                'description' => [
                    'tr' => 'Davy Jones alkollü tekne turu — her şey dahil.',
                    'en' => 'Davy Jones alcoholic boat tour — all-inclusive.',
                ],
            ],
            [
                'slug' => 'davy-jones-alkolsuz-tekne-turu',
                'name' => ['tr' => 'Davy Jones Alkolsüz Tekne Turu', 'en' => 'Davy Jones Alcohol-Free Boat Tour'],
                'description' => [
                    'tr' => 'Davy Jones alkolsüz tekne turu — her şey dahil.',
                    'en' => 'Davy Jones alcohol-free boat tour — all-inclusive.',
                ],
            ],
            [
                'slug' => 'soft-tekne-turu',
                'name' => ['tr' => 'Soft Tekne Turu', 'en' => 'Soft Boat Tour'],
                'description' => [
                    'tr' => 'Sakin ve aile dostu soft tekne turu — her şey dahil.',
                    'en' => 'Relaxed, family-friendly soft boat tour — all-inclusive.',
                ],
            ],
        ];

        foreach ($tours as $data) {
            $tour = Yacht::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'owner_id' => $owner->id,
                    'type' => 'tekne',
                    'with_crew' => true,
                    'currency' => 'EUR',
                    'unit_daily' => true,
                    'is_open' => true,
                    'capacity' => 35, // gunubirlik tur teknesi, ~30-40 kisi
                ])
            );

            $tour->forceFill(['status' => YachtStatus::Draft])->save();
        }
    }
}
