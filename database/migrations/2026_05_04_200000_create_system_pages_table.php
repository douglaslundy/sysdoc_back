<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_pages', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 80);
            $table->string('path', 120)->unique();
            $table->string('icone', 40)->nullable();
            $table->string('categoria', 60)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_pages');
    }
};
