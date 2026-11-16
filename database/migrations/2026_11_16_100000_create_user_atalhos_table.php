<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_atalhos')) {
            return;
        }

        Schema::create('user_atalhos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('titulo', 80);
            $table->string('url', 500);
            $table->string('icone', 40)->default('link');
            $table->unsignedTinyInteger('ordem')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'url']);
        });
    }

    public function down(): void
    {
        // No-op: não apaga atalhos dos usuários.
    }
};
