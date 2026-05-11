<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vigilancia_configs', function (Blueprint $table) {
            $table->id();
            $table->string('estado', 2)->default('');
            $table->string('nome_municipio')->default('');
            $table->string('nome_prefeitura')->default('');
            $table->string('cnpj_prefeitura', 14)->default('');
            $table->string('nome_secretaria')->default('');
            $table->string('cnpj_secretaria', 14)->default('');
            $table->string('divisao')->default('');
            $table->string('endereco')->default('');
            $table->string('cep', 8)->default('');
            $table->string('telefone')->default('');
            $table->string('email')->default('');
            $table->string('nome_responsavel')->default('');
            $table->string('cargo_responsavel')->default('');
            $table->string('grant_type')->default('ALVARÁ SANITÁRIO DE FUNCIONAMENTO');
            $table->json('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vigilancia_configs');
    }
};
