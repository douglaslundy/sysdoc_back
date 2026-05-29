<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sincronizacoes_cidadao', function (Blueprint $table) {
            $table->id();
            $table->string('job_id', 36)->unique();
            $table->enum('status', ['pending', 'analyzing', 'preview_ready', 'applying', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('total_esus')->default(0);
            $table->unsignedInteger('total_sysdoc')->default(0);
            $table->unsignedInteger('preview_criados')->default(0);
            $table->unsignedInteger('preview_atualizados')->default(0);
            $table->unsignedInteger('preview_obitos')->default(0);
            $table->unsignedInteger('preview_sem_alteracao')->default(0);
            $table->unsignedInteger('result_criados')->nullable();
            $table->unsignedInteger('result_atualizados')->nullable();
            $table->unsignedInteger('result_obitos')->nullable();
            $table->unsignedInteger('result_erros')->nullable();
            $table->unsignedBigInteger('iniciado_por');
            $table->unsignedBigInteger('aplicado_por')->nullable();
            $table->foreign('iniciado_por')->references('id')->on('users');
            $table->foreign('aplicado_por')->references('id')->on('users');
            $table->timestamp('analisado_em')->nullable();
            $table->timestamp('aplicado_em')->nullable();
            $table->text('erro_mensagem')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizacoes_cidadao');
    }
};
