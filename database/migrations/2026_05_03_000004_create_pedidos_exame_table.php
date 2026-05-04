<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos_exame', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->unsignedBigInteger('criado_por');
            $table->foreign('criado_por')->references('id')->on('users');
            $table->string('medico_solicitante', 100)->nullable();
            $table->date('data_pedido');
            $table->date('data_coleta')->nullable();
            $table->enum('status', ['solicitado', 'coletado', 'em_analise', 'liberado', 'cancelado'])->default('solicitado');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('client_id');
            $table->index('status');
            $table->index('data_pedido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_exame');
    }
};
