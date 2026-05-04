<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campo_referencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exame_campo_id');
            $table->foreign('exame_campo_id')->references('id')->on('exame_campos')->onDelete('cascade');
            $table->enum('perfil', ['geral', 'adulto_m', 'adulto_f', 'crianca', 'idoso', 'gestante'])->default('geral');
            $table->decimal('valor_min', 10, 3)->nullable();
            $table->decimal('valor_max', 10, 3)->nullable();
            $table->string('valor_texto', 200)->nullable();
            $table->string('descricao', 200)->nullable();
            $table->timestamps();

            $table->unique(['exame_campo_id', 'perfil']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campo_referencias');
    }
};
