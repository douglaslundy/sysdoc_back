<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almoxarifado_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('permitir_saida_sem_saldo')->default(false);
            $table->boolean('permitir_transferencia_entre_secretarias')->default(true);
            $table->boolean('exigir_justificativa_saida')->default(true);
            $table->boolean('exigir_localizacao_produto')->default(false);
            $table->boolean('notificar_estoque_minimo')->default(true);
            $table->unsignedInteger('estoque_minimo_alerta_percentual')->default(20);
            $table->boolean('permite_produto_sem_validade')->default(true);
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almoxarifado_configs');
    }
};
