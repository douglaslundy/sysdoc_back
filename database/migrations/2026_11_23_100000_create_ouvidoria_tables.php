<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('secretarias', function (Blueprint $t) {
            // Separada de "ativa": a secretaria só aparece no portal público se as duas forem verdadeiras.
            $t->boolean('ouvidoria_publica')->default(false)->after('ativa');
        });
        DB::table('secretarias')->where('slug', 'saude')->update(['ouvidoria_publica' => true]);

        Schema::create('ouvidoria_manifestacoes', function (Blueprint $t) {
            $t->id();
            $t->string('protocolo', 20)->unique();
            $t->string('senha_hash');
            $t->foreignId('secretaria_id')->constrained('secretarias');
            $t->string('tipo', 20);
            $t->string('assunto', 160);
            $t->text('descricao');
            $t->string('status', 20)->default('recebida')->index();
            $t->boolean('anonimo')->default(true);
            $t->string('nome', 120)->nullable();
            $t->string('email', 160)->nullable();
            $t->string('phone', 30)->nullable();
            $t->text('cpf')->nullable(); // cifrado (cast encrypted)
            $t->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('resposta')->nullable();
            $t->foreignId('respondida_por_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('respondida_em')->nullable();
            $t->timestamp('prazo_em')->nullable();
            $t->timestamp('prazo_avisado_em')->nullable();
            $t->unsignedTinyInteger('tentativas_falhas')->default(0);
            $t->timestamp('bloqueado_ate')->nullable();
            $t->timestamps();
            $t->index(['secretaria_id', 'status']);
        });

        Schema::create('ouvidoria_historico', function (Blueprint $t) {
            $t->id();
            $t->foreignId('manifestacao_id')->constrained('ouvidoria_manifestacoes')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // nulo = ação do cidadão
            $t->string('acao', 30);
            $t->string('status_de', 20)->nullable();
            $t->string('status_para', 20)->nullable();
            $t->foreignId('secretaria_de')->nullable()->constrained('secretarias')->nullOnDelete();
            $t->foreignId('secretaria_para')->nullable()->constrained('secretarias')->nullOnDelete();
            $t->text('observacao')->nullable();
            $t->boolean('interno')->default(false);
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('ouvidoria_anexos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('manifestacao_id')->constrained('ouvidoria_manifestacoes')->cascadeOnDelete();
            $t->string('disk', 20);
            $t->string('path');
            $t->string('original_name')->nullable();
            $t->string('mime_type', 100)->nullable();
            $t->unsignedBigInteger('size_bytes')->default(0);
            $t->timestamps();
        });

        // Secretarias de origem que continuam vendo (só leitura) a manifestação depois de um encaminhamento.
        Schema::create('ouvidoria_acessos_leitura', function (Blueprint $t) {
            $t->foreignId('manifestacao_id')->constrained('ouvidoria_manifestacoes')->cascadeOnDelete();
            $t->foreignId('secretaria_id')->constrained('secretarias')->cascadeOnDelete();
            $t->primary(['manifestacao_id', 'secretaria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ouvidoria_acessos_leitura');
        Schema::dropIfExists('ouvidoria_anexos');
        Schema::dropIfExists('ouvidoria_historico');
        Schema::dropIfExists('ouvidoria_manifestacoes');
        Schema::table('secretarias', function (Blueprint $t) {
            $t->dropColumn('ouvidoria_publica');
        });
    }
};
