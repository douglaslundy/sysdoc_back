<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kanban_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocol_id')->nullable()->unique()->constrained('protocols')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo', 200);
            $table->text('descricao')->nullable();
            $table->string('status', 40)->default('novo');
            $table->string('prioridade', 20)->default('normal');
            $table->date('vencimento')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['status', 'prioridade'], 'kanban_status_prioridade_idx');
            $table->index(['responsavel_id', 'vencimento'], 'kanban_responsavel_vencimento_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_tasks');
    }
};
