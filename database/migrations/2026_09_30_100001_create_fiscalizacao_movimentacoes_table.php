<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fiscalizacao_movimentacoes')) {
            return;
        }

        Schema::create('fiscalizacao_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscalizacao_id')->constrained('fiscalizacoes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 40);
            $table->text('descricao')->nullable();
            $table->json('dados')->nullable();
            $table->boolean('publico')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['fiscalizacao_id', 'id'], 'fiscalizacao_mov_fisc_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscalizacao_movimentacoes');
    }
};
