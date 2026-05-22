<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('tagline')->nullable();
            $table->json('description')->nullable();
            $table->string('inspired_perfume_name')->nullable();
            $table->foreignId('inspired_brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->unsignedSmallInteger('volume_ml')->default(50);
            $table->string('gender', 16);
            $table->decimal('price_uah', 10, 2);
            $table->decimal('price_eur', 10, 2);
            $table->boolean('in_stock')->default(true);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->foreignId('perfume_family_id')->nullable()->constrained('perfume_families')->nullOnDelete();
            $table->foreignId('concentration_id')->nullable()->constrained('concentrations')->nullOnDelete();
            $table->foreignId('series_id')->nullable()->constrained('series')->nullOnDelete();
            $table->timestamps();

            $table->index('is_published');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
