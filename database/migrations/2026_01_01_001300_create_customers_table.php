<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MÜŞTERİLER — üyelikten bağımsız CRM kaydı.
 *
 * Üyelik kaldırılıyor; müşteri bilgisi artık `users` üzerinde değil burada
 * duruyor. Rezervasyon formu gelince e-postaya göre eşleşen müşteri aranır,
 * yoksa açılır — böylece aynı kişinin tüm rezervasyonları tek kayıt altında
 * toplanır. Şifre/giriş alanı YOK, bu tablo hiçbir zaman kimlik doğrulamada
 * kullanılmayacak.
 *
 * Referans desen: Marmaris Travel Center (customers + customer_id ile bağlı
 * rezervasyon).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                // E-posta müşteriyi tekilleştiren alan; aynı adres iki kayıt açmasın.
                $table->string('email')->unique();
                $table->string('phone', 32)->nullable();
                $table->string('whatsapp', 32)->nullable();
                $table->string('locale', 5)->default('tr');
                $table->text('admin_note')->nullable();
                $table->timestamps();

                $table->index('name');
            });
        }

        if (! Schema::hasColumn('reservations', 'customer_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                // nullable: eski kayıtlar taşınana kadar boş kalabilir, ayrıca
                // müşteri silinse bile rezervasyon kaybolmasın.
                $table->foreignId('customer_id')->nullable()->after('user_id')
                    ->constrained('customers')->nullOnDelete();
            });
        }

        // customer_notes artık üyeye değil müşteriye bağlanıyor.
        if (Schema::hasTable('customer_notes') && ! Schema::hasColumn('customer_notes', 'customer_id')) {
            Schema::table('customer_notes', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('id')
                    ->constrained('customers')->cascadeOnDelete();
            });

            /*
             * user_id zorunlulugu kalkiyor: panelden acilan yeni notta artik
             * uye yok, yalnizca customer_id yaziliyor. Kolon simdilik duruyor
             * (eski notlarin izi), uyelik tamamen sokulunce kaldirilacak.
             *
             * change() kullaniliyor -- ham "ALTER ... MODIFY" MySQL'e ozgu,
             * testler SQLite uzerinde kosuyor.
             */
            Schema::table('customer_notes', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customer_notes', 'customer_id')) {
            Schema::table('customer_notes', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            });
        }

        if (Schema::hasColumn('reservations', 'customer_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            });
        }

        Schema::dropIfExists('customers');
    }
};
