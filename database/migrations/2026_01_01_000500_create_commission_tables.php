<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uc kademeli oran: global > owner > yacht. En dar tanim gecerli.
        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 10)->default('global'); // global | owner | yacht
            $table->unsignedBigInteger('target_id')->nullable();
            $table->decimal('rate', 5, 2); // %
            $table->date('effective_from')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['scope', 'target_id']);
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('revenue', 14, 2)->default(0);
            $table->decimal('commission', 14, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->unsignedInteger('reservation_count')->default(0);
            $table->string('status', 20)->default('pending'); // pending | collected | cancelled
            $table->date('collected_at')->nullable();
            $table->string('invoice_no')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'year', 'month']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreign('collection_id')->references('id')->on('collections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
        });
        Schema::dropIfExists('collections');
        Schema::dropIfExists('commission_settings');
    }
};
