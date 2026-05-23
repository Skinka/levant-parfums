<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->json('slug');
            $table->json('title');
            $table->json('intro')->nullable();
            $table->json('content');
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index('is_published');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pages ADD UNIQUE pages_slug_uk_uniq ((CAST(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.uk')) AS CHAR(191))))");
            DB::statement("ALTER TABLE pages ADD UNIQUE pages_slug_en_uniq ((CAST(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.en')) AS CHAR(191))))");
        } elseif (DB::getDriverName() === 'sqlite') {
            // SQLite 3.38+ supports JSON path expressions in UNIQUE indexes.
            DB::statement("CREATE UNIQUE INDEX pages_slug_uk_uniq ON pages ((json_extract(slug, '$.uk')))");
            DB::statement("CREATE UNIQUE INDEX pages_slug_en_uniq ON pages ((json_extract(slug, '$.en')))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
