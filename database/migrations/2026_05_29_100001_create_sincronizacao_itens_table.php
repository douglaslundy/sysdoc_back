<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sincronizacao_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sincronizacao_id');
            $table->foreign('sincronizacao_id')->references('id')->on('sincronizacoes_cidadao')->cascadeOnDelete();
            $table->enum('acao', ['criar', 'atualizar', 'obito']);
            $table->string('cpf', 18)->nullable();
            $table->string('cns', 15)->nullable();
            $table->string('nome_esus', 150);
            $table->unsignedBigInteger('client_id')->nullable();
            $table->json('payload');
            $table->boolean('aplicado')->default(false);
            $table->string('erro', 255)->nullable();
            $table->index(['sincronizacao_id', 'acao']);
            $table->index(['sincronizacao_id', 'aplicado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizacao_itens');
    }
};
