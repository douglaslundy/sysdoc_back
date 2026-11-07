<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('secretarias')) {
            Schema::create('secretarias', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 120)->unique();
                $table->string('slug', 60)->unique();
                $table->boolean('ativa')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_secretarias')) {
            Schema::create('user_secretarias', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('secretaria_id')->constrained('secretarias')->cascadeOnDelete();
                $table->primary(['user_id', 'secretaria_id']);
            });
        }

        if (! Schema::hasTable('secretaria_pages')) {
            Schema::create('secretaria_pages', function (Blueprint $table) {
                $table->foreignId('secretaria_id')->constrained('secretarias')->cascadeOnDelete();
                $table->foreignId('system_page_id')->constrained('system_pages')->cascadeOnDelete();
                $table->primary(['secretaria_id', 'system_page_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('secretaria_pages');
        Schema::dropIfExists('user_secretarias');
        Schema::dropIfExists('secretarias');
    }
};
