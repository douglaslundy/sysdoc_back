<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almoxarifado_secretarias', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120)->unique();
            $table->string('sigla', 20)->nullable();
            $table->string('responsavel', 120)->nullable();
            $table->string('contato', 120)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('almoxarifado_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120)->unique();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('almoxarifado_especies', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120)->unique();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('almoxarifado_unidades_medida', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 80)->unique();
            $table->string('sigla', 20)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('almoxarifado_fornecedores', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('documento', 30)->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('contato', 120)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('almoxarifado_localizacoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('almoxarifado', 120)->nullable();
            $table->string('sala', 80)->nullable();
            $table->string('corredor', 80)->nullable();
            $table->string('estante', 80)->nullable();
            $table->string('prateleira', 80)->nullable();
            $table->string('gaveta', 80)->nullable();
            $table->string('caixa', 80)->nullable();
            $table->string('posicao', 80)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('almoxarifado_produtos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->string('codigo_interno', 60)->unique();
            $table->string('codigo_barras', 80)->nullable()->index();
            $table->string('qr_code', 255)->nullable();
            $table->foreignId('almoxarifado_categoria_id')->nullable()->constrained('almoxarifado_categorias')->nullOnDelete();
            $table->foreignId('almoxarifado_especie_id')->nullable()->constrained('almoxarifado_especies')->nullOnDelete();
            $table->foreignId('almoxarifado_unidade_medida_id')->nullable()->constrained('almoxarifado_unidades_medida')->nullOnDelete();
            $table->foreignId('almoxarifado_fornecedor_id')->nullable()->constrained('almoxarifado_fornecedores')->nullOnDelete();
            $table->foreignId('almoxarifado_localizacao_id')->nullable()->constrained('almoxarifado_localizacoes')->nullOnDelete();
            $table->string('marca', 120)->nullable();
            $table->string('modelo', 120)->nullable();
            $table->string('fabricante', 120)->nullable();
            $table->string('numero_serie', 120)->nullable();
            $table->string('lote', 80)->nullable();
            $table->date('validade')->nullable();
            $table->decimal('estoque_minimo', 14, 3)->default(0);
            $table->decimal('estoque_maximo', 14, 3)->nullable();
            $table->string('almoxarifado', 120)->nullable();
            $table->string('sala', 80)->nullable();
            $table->string('corredor', 80)->nullable();
            $table->string('estante', 80)->nullable();
            $table->string('prateleira', 80)->nullable();
            $table->string('gaveta', 80)->nullable();
            $table->string('caixa', 80)->nullable();
            $table->string('posicao', 80)->nullable();
            $table->text('observacao_localizacao')->nullable();
            $table->string('imagem_url', 255)->nullable();
            $table->boolean('ativo')->default(true);
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });

        Schema::create('almoxarifado_estoques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almoxarifado_produto_id')->constrained('almoxarifado_produtos')->cascadeOnDelete();
            $table->foreignId('almoxarifado_secretaria_id')->nullable()->constrained('almoxarifado_secretarias')->nullOnDelete();
            $table->decimal('quantidade_disponivel', 14, 3)->default(0);
            $table->decimal('quantidade_reservada', 14, 3)->default(0);
            $table->decimal('quantidade_em_separacao', 14, 3)->default(0);
            $table->decimal('quantidade_entregue', 14, 3)->default(0);
            $table->timestamps();

            $table->unique(['almoxarifado_produto_id', 'almoxarifado_secretaria_id'], 'almox_estoque_produto_secretaria_unique');
        });

        Schema::create('almoxarifado_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almoxarifado_produto_id')->constrained('almoxarifado_produtos')->cascadeOnDelete();
            $table->foreignId('almoxarifado_secretaria_origem_id')->nullable()->constrained('almoxarifado_secretarias', 'id', 'almox_mov_sec_origem_fk')->nullOnDelete();
            $table->foreignId('almoxarifado_secretaria_destino_id')->nullable()->constrained('almoxarifado_secretarias', 'id', 'almox_mov_sec_dest_fk')->nullOnDelete();
            $table->string('tipo', 30);
            $table->decimal('quantidade', 14, 3);
            $table->decimal('saldo_anterior', 14, 3)->nullable();
            $table->decimal('saldo_posterior', 14, 3)->nullable();
            $table->string('motivo', 150)->nullable();
            $table->text('observacao')->nullable();
            $table->string('documento_tipo', 60)->nullable();
            $table->unsignedBigInteger('documento_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['documento_tipo', 'documento_id'], 'almox_mov_documento_idx');
        });

        Schema::create('almoxarifado_requisicoes', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 40)->unique();
            $table->foreignId('almoxarifado_secretaria_id')->constrained('almoxarifado_secretarias')->restrictOnDelete();
            $table->string('solicitante', 150);
            $table->date('data_solicitacao');
            $table->string('status', 30)->default('recebida');
            $table->text('justificativa')->nullable();
            $table->text('observacoes')->nullable();
            $table->foreignId('usuario_responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('data_atendimento')->nullable();
            $table->dateTime('data_entrega')->nullable();
            $table->timestamps();
        });

        Schema::create('almoxarifado_requisicao_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almoxarifado_requisicao_id')->constrained('almoxarifado_requisicoes')->cascadeOnDelete();
            $table->foreignId('almoxarifado_produto_id')->constrained('almoxarifado_produtos')->restrictOnDelete();
            $table->decimal('quantidade_solicitada', 14, 3);
            $table->decimal('quantidade_atendida', 14, 3)->default(0);
            $table->decimal('quantidade_entregue', 14, 3)->default(0);
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->unique(['almoxarifado_requisicao_id', 'almoxarifado_produto_id'], 'almox_req_item_unique');
        });

        Schema::create('almoxarifado_arquivos', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type', 120);
            $table->unsignedBigInteger('attachable_id');
            $table->string('nome_original', 255);
            $table->string('caminho', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('tamanho_bytes')->nullable();
            $table->string('tipo', 60)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id'], 'almox_arquivo_attachable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almoxarifado_arquivos');
        Schema::dropIfExists('almoxarifado_requisicao_itens');
        Schema::dropIfExists('almoxarifado_requisicoes');
        Schema::dropIfExists('almoxarifado_movimentacoes');
        Schema::dropIfExists('almoxarifado_estoques');
        Schema::dropIfExists('almoxarifado_produtos');
        Schema::dropIfExists('almoxarifado_localizacoes');
        Schema::dropIfExists('almoxarifado_fornecedores');
        Schema::dropIfExists('almoxarifado_unidades_medida');
        Schema::dropIfExists('almoxarifado_especies');
        Schema::dropIfExists('almoxarifado_categorias');
        Schema::dropIfExists('almoxarifado_secretarias');
    }
};
