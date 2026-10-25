<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('iluminacao_poste_movimentacoes')) {
            return;
        }

        Schema::create('iluminacao_poste_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poste_id')->constrained('iluminacao_postes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 30);
            $table->string('status_anterior', 20)->nullable();
            $table->string('status_novo', 20)->nullable();
            $table->text('motivo')->nullable();
            $table->json('dados')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['poste_id', 'id'], 'iluminacao_mov_poste_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iluminacao_poste_movimentacoes');
    }
};
