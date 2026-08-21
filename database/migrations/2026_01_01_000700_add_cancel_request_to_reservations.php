<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Müşteri iptal talebi: kendisi iptal EDEMEZ, talep eder; onayı yat sahibi verir.
            $table->timestamp('cancel_requested_at')->nullable()->after('completed_at');
            $table->text('cancel_request_reason')->nullable()->after('cancel_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['cancel_requested_at', 'cancel_request_reason']);
        });
    }
};
