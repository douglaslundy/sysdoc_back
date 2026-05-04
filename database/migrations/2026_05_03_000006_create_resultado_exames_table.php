<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultado_exames', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_exame_id')->unique();
            $table->foreign('pedido_exame_id')->references('id')->on('pedidos_exame')->onDelete('cascade');
            $table->unsignedBigInteger('liberado_por')->nullable();
            $table->foreign('liberado_por')->references('id')->on('users');
            $table->string('protocolo', 20)->unique()->nullable();
            $table->string('senha_hash', 255)->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->timestamp('data_liberacao')->nullable();
            $table->timestamp('data_validade')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('protocolo');
            $table->index('pedido_exame_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultado_exames');
    }
};
