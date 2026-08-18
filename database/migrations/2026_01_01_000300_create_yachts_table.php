<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yachts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete(); // kalkis limani

            $table->json('name');
            $table->string('slug')->unique();
            $table->json('description')->nullable();
            $table->json('rules')->nullable();

            $table->string('type')->nullable(); // motoryat | gulet | katamaran | yelkenli | surat_teknesi
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('build_year')->nullable();
            $table->decimal('length_m', 6, 2)->nullable();
            $table->unsignedTinyInteger('cabins')->nullable();
            $table->unsignedTinyInteger('beds')->nullable();
            $table->unsignedTinyInteger('wc')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable(); // gunluk tur kapasitesi
            $table->unsignedSmallInteger('sleep_capacity')->nullable();
            $table->string('engine')->nullable();
            $table->boolean('with_crew')->default(true); // kaptanli mi

            // Kiralama birimleri (yat sahibi secer)
            $table->boolean('unit_hourly')->default(false);
            $table->boolean('unit_daily')->default(true);
            $table->boolean('unit_weekly')->default(false);
            $table->string('currency', 3)->default('EUR');

            $table->unsignedSmallInteger('turnaround_minutes')->default(0); // hazirlik payi
            $table->time('day_start')->nullable();   // saatlik icin gun ici calisma araligi
            $table->time('day_end')->nullable();
            $table->time('checkin_time')->nullable();
            $table->time('checkout_time')->nullable();
            $table->unsignedTinyInteger('weekly_start_dow')->nullable(); // 0=pazar..6=cumartesi

            $table->boolean('is_open')->default(true);   // Rezervasyona Acik / Kapali salteri
            $table->string('status')->default('draft')->index(); // draft | pending | published | rejected
            $table->boolean('is_featured')->default(false);
            $table->text('reject_reason')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->decimal('price_from', 12, 2)->nullable(); // liste kartinda "…den baslayan" - denormalize
            $table->string('price_from_unit', 10)->nullable();
            $table->unsignedInteger('view_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_open']);
        });

        Schema::create('feature_yacht', function (Blueprint $table) {
            $table->foreignId('yacht_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->primary(['yacht_id', 'feature_id']);
        });

        Schema::create('yacht_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yacht_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->json('alt')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();
        });

        Schema::create('yacht_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yacht_id')->constrained()->cascadeOnDelete();
            $table->string('unit', 10); // hour | day | week
            $table->string('label')->nullable(); // "Yuksek sezon" vb.
            $table->date('season_start')->nullable(); // null = temel fiyat
            $table->date('season_end')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedSmallInteger('min_duration')->default(1); // en az kac saat/gun/hafta
            $table->timestamps();

            $table->index(['yacht_id', 'unit', 'season_start', 'season_end'], 'yacht_rates_lookup_idx');
        });

        Schema::create('yacht_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yacht_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('calculation', 20)->default('fixed'); // fixed | per_person | per_day
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yacht_extras');
        Schema::dropIfExists('yacht_rates');
        Schema::dropIfExists('yacht_photos');
        Schema::dropIfExists('feature_yacht');
        Schema::dropIfExists('yachts');
    }
};
