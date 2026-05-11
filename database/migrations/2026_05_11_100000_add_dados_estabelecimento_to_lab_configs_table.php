<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_configs', function (Blueprint $table) {
            $table->string('nome_estabelecimento')->nullable()->default('');
            $table->string('razao_social')->nullable()->default('');
            $table->string('endereco_rua')->nullable()->default('');
            $table->string('endereco_numero')->nullable()->default('');
            $table->string('endereco_bairro')->nullable()->default('');
            $table->string('endereco_cep', 8)->nullable()->default('');
            $table->string('telefone')->nullable()->default('');
            $table->string('cnpj', 14)->nullable()->default('');
            $table->string('email_lab')->nullable()->default('');
            $table->text('rodape1')->nullable();
            $table->text('rodape2')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lab_configs', function (Blueprint $table) {
            $table->dropColumn([
                'nome_estabelecimento', 'razao_social',
                'endereco_rua', 'endereco_numero', 'endereco_bairro', 'endereco_cep',
                'telefone', 'cnpj', 'email_lab',
                'rodape1', 'rodape2',
            ]);
        });
    }
};
