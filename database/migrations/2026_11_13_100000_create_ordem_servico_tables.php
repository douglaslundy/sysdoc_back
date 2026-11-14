<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('os_tipos_servico', function (Blueprint $t) {
            $t->id();
            $t->foreignId('secretaria_id')->constrained('secretarias')->cascadeOnDelete();
            $t->string('nome', 120);
            $t->boolean('ativo')->default(true);
            $t->timestamps();
            $t->unique(['secretaria_id', 'nome']);
        });

        Schema::create('ordens_servico', function (Blueprint $t) {
            $t->id();
            $t->string('numero', 20)->unique();
            // Secretaria DESTINO (quem executa). O solicitante pode ser de qualquer secretaria.
            $t->foreignId('secretaria_id')->constrained('secretarias');
            $t->foreignId('tipo_servico_id')->constrained('os_tipos_servico');
            $t->foreignId('solicitante_id')->constrained('users');
            $t->foreignId('tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('local', 160);
            $t->string('endereco', 255)->nullable();
            $t->text('descricao');
            $t->text('problema')->nullable();
            $t->string('prioridade', 10)->default('normal');
            $t->date('prazo')->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->string('contato_nome', 120)->nullable();
            $t->string('contato_telefone', 30)->nullable();
            $t->string('status', 20)->default('aberta')->index();
            $t->text('fechamento_relato')->nullable();
            $t->text('materiais')->nullable();
            $t->unsignedInteger('tempo_gasto_min')->nullable();
            $t->timestamp('concluida_em')->nullable();
            $t->timestamp('prazo_avisado_em')->nullable();
            $t->timestamps();
            $t->index(['secretaria_id', 'status']);
        });

        Schema::create('os_fotos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();
            $t->string('fase', 12); // abertura | fechamento
            $t->foreignId('enviada_por')->nullable()->constrained('users')->nullOnDelete();
            $t->string('disk', 20);
            $t->string('path');
            $t->string('original_name')->nullable();
            $t->string('mime_type', 100)->nullable();
            $t->unsignedBigInteger('size_bytes')->default(0);
            $t->timestamps();
        });

        Schema::create('os_historico', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('acao', 30);
            $t->string('status_de', 20)->nullable();
            $t->string('status_para', 20)->nullable();
            $t->text('observacao')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        // secretaria_id nulo = padrão global do admin.
        Schema::create('os_configuracoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('secretaria_id')->nullable()->unique()->constrained('secretarias')->cascadeOnDelete();
            $t->boolean('solicitante_aponta_tecnico')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('os_configuracoes');
        Schema::dropIfExists('os_historico');
        Schema::dropIfExists('os_fotos');
        Schema::dropIfExists('ordens_servico');
        Schema::dropIfExists('os_tipos_servico');
    }
};
