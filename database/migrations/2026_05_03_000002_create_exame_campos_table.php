<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exame_campos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exame_id');
            $table->foreign('exame_id')->references('id')->on('exames')->onDelete('cascade');
            $table->string('nome', 100);
            $table->string('descricao', 200)->nullable();
            $table->enum('tipo_valor', ['numerico', 'texto', 'booleano', 'selecao'])->default('numerico');
            $table->string('unidade', 30)->nullable();
            $table->json('opcoes_selecao')->nullable();
            $table->integer('ordem')->default(0);
            $table->boolean('obrigatorio')->default(true);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['exame_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exame_campos');
    }
};
