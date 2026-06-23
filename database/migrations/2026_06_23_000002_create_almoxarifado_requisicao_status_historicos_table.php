<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almoxarifado_requisicao_status_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almoxarifado_requisicao_id')->constrained('almoxarifado_requisicoes')->cascadeOnDelete();
            $table->string('status_anterior', 30)->nullable();
            $table->string('novo_status', 30);
            $table->text('observacao')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almoxarifado_requisicao_status_historicos');
    }
};
