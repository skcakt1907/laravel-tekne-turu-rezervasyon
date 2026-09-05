<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ozel tekne kiralamadan kisi-basi grup turuna gecis: tek "guests" sayisi
 * yerine yetiskin/cocuk ayri sayilir (cocuk fiyati farkli olabiliyor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedSmallInteger('adults')->default(1)->after('guests');
            $table->unsignedSmallInteger('children')->default(0)->after('adults');
        });

        DB::table('reservations')->update(['adults' => DB::raw('guests')]);

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('guests');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedSmallInteger('guests')->default(1)->after('ends_at');
        });

        DB::table('reservations')->update(['guests' => DB::raw('adults + children')]);

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['adults', 'children']);
        });
    }
};
