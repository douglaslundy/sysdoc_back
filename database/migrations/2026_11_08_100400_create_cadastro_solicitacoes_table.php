<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cadastro_solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);
            $table->string('preferred_name', 50)->nullable();
            $table->string('email', 100);
            $table->string('cpf', 18);
            $table->string('phone', 30)->nullable();
            $table->string('password_hash');
            $table->foreignId('secretaria_id')->constrained('secretarias');
            $table->string('cargo', 80);
            $table->string('observacao', 200)->nullable();
            $table->enum('status', ['pendente', 'aprovada', 'recusada'])->default('pendente');
            $table->unsignedBigInteger('protocol_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('motivo_recusa')->nullable();
            $table->unsignedBigInteger('decidido_por_id')->nullable();
            $table->timestamp('decidido_em')->nullable();
            $table->timestamps();

            $table->index(['status', 'cpf']);
            $table->index(['status', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadastro_solicitacoes');
    }
};
