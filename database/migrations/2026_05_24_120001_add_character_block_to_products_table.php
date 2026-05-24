<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('character')->nullable()->after('tagline');
            $table->json('why')->nullable()->after('character');
            $table->unsignedTinyInteger('sillage_score')->nullable()->after('why');
            $table->unsignedTinyInteger('longevity_hours')->nullable()->after('sillage_score');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['character', 'why', 'sillage_score', 'longevity_hours']);
        });
    }
};
