<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64)->index();
            $table->string('status', 16)->default('new')->index();
            $table->json('data');
            $table->nullableMorphs('subject');
            $table->json('meta')->nullable();
            $table->string('locale', 5)->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
