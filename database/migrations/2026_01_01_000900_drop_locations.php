<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Site artik sadece Marmaris'te gunubirlik tekne turlari satiyor;
        // liman/bolge SEO sayfalari ve yat->liman iliskisi kaldirildi.
        Schema::table('yachts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::dropIfExists('locations');
    }

    public function down(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(3);
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('description')->nullable();
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->string('cover')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index(['level', 'is_active']);
        });

        Schema::table('yachts', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('owner_id')->constrained('locations')->nullOnDelete();
        });
    }
};
