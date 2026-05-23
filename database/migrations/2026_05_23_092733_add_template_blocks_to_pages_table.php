<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('template', 32)->default('simple')->after('content')->index();
            $table->json('blocks')->nullable()->after('template');
            $table->boolean('is_homepage')->default(false)->after('is_published');
        });

        // content is no longer required (landing pages do not use it).
        // For SQLite, Laravel rebuilds the table for any column change which
        // destroys our JSON-expression unique indexes on slug. We drop and
        // recreate them around the change so the rebuild can complete.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS pages_slug_uk_uniq');
            DB::statement('DROP INDEX IF EXISTS pages_slug_en_uniq');
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->json('content')->nullable()->change();
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX pages_slug_uk_uniq ON pages ((json_extract(slug, '$.uk')))");
            DB::statement("CREATE UNIQUE INDEX pages_slug_en_uniq ON pages ((json_extract(slug, '$.en')))");
        }

        // Exactly one homepage. MySQL has no partial-index syntax → use CASE
        // expression (NULLs are not considered duplicates by UNIQUE).
        // SQLite supports partial unique indexes natively via WHERE.
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'CREATE UNIQUE INDEX pages_is_homepage_uniq ON pages '
                .'((CASE WHEN is_homepage = 1 THEN 1 ELSE NULL END))'
            );
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement(
                'CREATE UNIQUE INDEX pages_is_homepage_uniq ON pages (is_homepage) '
                .'WHERE is_homepage = 1'
            );
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('DROP INDEX pages_is_homepage_uniq ON pages');
        } elseif ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS pages_is_homepage_uniq');
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['template']);
            $table->dropColumn(['template', 'blocks', 'is_homepage']);
        });

        // Restore content as NOT NULL (best-effort; will fail if any row has NULL).
        Schema::table('pages', function (Blueprint $table) {
            $table->json('content')->nullable(false)->change();
        });
    }
};
