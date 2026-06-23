<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocol_organizational_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('protocol_organizational_units', 'id', 'prot_org_parent_fk')->nullOnDelete();
            $table->string('tipo', 40)->default('secretaria');
            $table->string('codigo', 60)->nullable();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['tipo', 'ativo'], 'prot_org_tipo_ativo_idx');
        });

        Schema::create('protocol_user_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('protocol_organizational_unit_id')->constrained('protocol_organizational_units')->cascadeOnDelete();
            $table->string('papel', 40)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'protocol_organizational_unit_id'], 'prot_user_unit_unique');
        });

        Schema::create('protocol_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('allow_external_protocols')->default(true);
            $table->boolean('allow_reopen')->default(true);
            $table->boolean('notify_internal')->default(true);
            $table->boolean('notify_email')->default(false);
            $table->boolean('notify_whatsapp')->default(false);
            $table->string('default_priority', 20)->default('normal');
            $table->unsignedInteger('default_due_days')->default(5);
            $table->string('evolution_base_url', 255)->nullable();
            $table->text('evolution_api_key')->nullable();
            $table->string('evolution_default_session', 120)->nullable();
            $table->boolean('evolution_enabled')->default(false);
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });

        Schema::create('protocol_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->string('modulo', 80);
            $table->string('gatilho', 80);
            $table->json('condicoes')->nullable();
            $table->json('canais')->nullable();
            $table->json('destinatarios')->nullable();
            $table->text('template')->nullable();
            $table->boolean('ativo')->default(true);
            $table->string('frequencia', 60)->nullable();
            $table->boolean('prevenir_duplicidade')->default(true);
            $table->timestamps();
        });

        Schema::create('protocols', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 40)->unique();
            $table->string('assunto', 200);
            $table->text('descricao')->nullable();
            $table->string('tipo', 40)->default('interno');
            $table->string('status', 40)->default('novo');
            $table->string('prioridade', 20)->default('normal');
            $table->string('solicitante_tipo', 20)->default('interno');
            $table->string('solicitante_nome', 150)->nullable();
            $table->string('solicitante_documento', 40)->nullable();
            $table->foreignId('origem_unit_id')->nullable()->constrained('protocol_organizational_units')->nullOnDelete();
            $table->foreignId('destino_unit_id')->nullable()->constrained('protocol_organizational_units')->nullOnDelete();
            $table->foreignId('responsavel_atual_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('criado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('prazo_atendimento')->nullable();
            $table->dateTime('recebido_em')->nullable();
            $table->dateTime('encaminhado_em')->nullable();
            $table->dateTime('encerrado_em')->nullable();
            $table->dateTime('reaberto_em')->nullable();
            $table->dateTime('cancelado_em')->nullable();
            $table->text('justificativa_encerramento')->nullable();
            $table->text('justificativa_cancelamento')->nullable();
            $table->boolean('novo')->default(true);
            $table->boolean('vencido')->default(false);
            $table->timestamps();

            $table->index(['status', 'prioridade'], 'prot_status_prioridade_idx');
            $table->index(['origem_unit_id', 'destino_unit_id'], 'prot_origem_destino_idx');
            $table->index(['prazo_atendimento', 'vencido'], 'prot_prazo_vencido_idx');
        });

        Schema::create('protocol_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocol_id')->constrained('protocols')->cascadeOnDelete();
            $table->foreignId('from_unit_id')->nullable()->constrained('protocol_organizational_units')->nullOnDelete();
            $table->foreignId('to_unit_id')->nullable()->constrained('protocol_organizational_units')->nullOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 60);
            $table->string('status_anterior', 40)->nullable();
            $table->string('status_novo', 40)->nullable();
            $table->text('observacao')->nullable();
            $table->json('dados')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['protocol_id', 'acao'], 'prot_move_protocol_acao_idx');
        });

        Schema::create('protocol_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocol_id')->constrained('protocols')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 30)->default('comentario');
            $table->text('conteudo');
            $table->boolean('privado')->default(false);
            $table->timestamps();
        });

        Schema::create('protocol_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocol_id')->constrained('protocols')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nome_original', 255);
            $table->string('caminho', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('tamanho_bytes')->nullable();
            $table->string('descricao', 255)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('protocol_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocol_id')->nullable()->constrained('protocols')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('canal', 30);
            $table->string('titulo', 150);
            $table->text('mensagem');
            $table->string('status_envio', 30)->default('pendente');
            $table->dateTime('lida_em')->nullable();
            $table->dateTime('enviada_em')->nullable();
            $table->text('erro')->nullable();
            $table->json('dados')->nullable();
            $table->timestamps();

            $table->index(['status_envio', 'canal'], 'prot_notif_status_canal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_notifications');
        Schema::dropIfExists('protocol_attachments');
        Schema::dropIfExists('protocol_comments');
        Schema::dropIfExists('protocol_movements');
        Schema::dropIfExists('protocols');
        Schema::dropIfExists('protocol_alerts');
        Schema::dropIfExists('protocol_configs');
        Schema::dropIfExists('protocol_user_units');
        Schema::dropIfExists('protocol_organizational_units');
    }
};
