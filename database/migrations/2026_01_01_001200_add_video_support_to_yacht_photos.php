<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Galeri artik video da tasiyor. Tablo adi `yacht_photos` olarak kaliyor
 * (canlida veri var, yeniden adlandirmanin faydasi yok) ama icerik "medya".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yacht_photos', function (Blueprint $table) {
            $table->string('type', 10)->default('image')->after('path'); // image | video
            $table->string('poster')->nullable()->after('type');         // video kapak karesi
        });
    }

    public function down(): void
    {
        Schema::table('yacht_photos', function (Blueprint $table) {
            $table->dropColumn(['type', 'poster']);
        });
    }
};
