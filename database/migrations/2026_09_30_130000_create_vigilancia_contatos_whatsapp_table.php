<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vigilancia_contatos_whatsapp')) {
            return;
        }

        Schema::create('vigilancia_contatos_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('telefone', 20); // somente dígitos (DDD + número)
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vigilancia_contatos_whatsapp');
    }
};
