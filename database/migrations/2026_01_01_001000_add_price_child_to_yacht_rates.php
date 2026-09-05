<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yacht_rates', function (Blueprint $table) {
            $table->decimal('price_child', 12, 2)->nullable()->after('price'); // bos = yetiskinle ayni
        });
    }

    public function down(): void
    {
        Schema::table('yacht_rates', function (Blueprint $table) {
            $table->dropColumn('price_child');
        });
    }
};
