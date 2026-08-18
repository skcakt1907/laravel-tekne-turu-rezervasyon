<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('yacht_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete(); // onay aninda dondurulur
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // uyeyse

            // Musteri bilgileri (uyeliksiz talep)
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 32);
            $table->string('customer_whatsapp', 32)->nullable();
            $table->string('customer_locale', 5)->default('tr');

            // Tek zaman modeli: saatlik/gunluk/haftalik hepsi tarih+saat
            $table->string('unit', 10);           // hour | day | week
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('guests')->default(1);
            $table->text('message')->nullable();

            $table->string('status', 20)->default('pending')->index(); // pending|approved|rejected|cancelled|completed|no_show

            // Tahmini tutar (odeme alinmiyor)
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('extras_amount', 12, 2)->default(0);
            $table->decimal('estimated_total', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->json('price_breakdown')->nullable();
            $table->json('selected_extras')->nullable();

            // Komisyon - onay aninda dondurulur
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->decimal('commission_amount', 12, 2)->nullable();
            $table->foreignId('collection_id')->nullable(); // aylik tahsilat dokumu (FK sonraki migration)

            $table->string('source', 20)->default('web'); // web | admin | phone | whatsapp
            $table->string('access_token', 64)->nullable()->unique(); // tek kullanimlik guvenli baglanti
            $table->text('admin_note')->nullable();
            $table->text('reject_reason')->nullable();

            $table->timestamp('responded_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['yacht_id', 'status', 'starts_at', 'ends_at'], 'reservations_conflict_idx');
            $table->index(['owner_id', 'status']);
        });

        Schema::create('reservation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);           // created | approved | rejected | cancelled | completed | note
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->string('channel', 20)->default('panel'); // panel | whatsapp | link | system
            $table->string('ip', 45)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('blocked_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yacht_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason', 20)->default('manual'); // reservation | manual | maintenance
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['yacht_id', 'starts_at', 'ends_at'], 'blocked_periods_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_periods');
        Schema::dropIfExists('reservation_logs');
        Schema::dropIfExists('reservations');
    }
};
