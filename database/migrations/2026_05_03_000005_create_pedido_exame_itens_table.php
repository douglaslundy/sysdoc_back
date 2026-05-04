<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_exame_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_exame_id');
            $table->foreign('pedido_exame_id')->references('id')->on('pedidos_exame')->onDelete('cascade');
            $table->unsignedBigInteger('exame_id');
            $table->foreign('exame_id')->references('id')->on('exames');
            $table->timestamps();

            $table->unique(['pedido_exame_id', 'exame_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_exame_itens');
    }
};
