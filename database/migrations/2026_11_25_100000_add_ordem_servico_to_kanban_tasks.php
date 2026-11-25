<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kanban_tasks') || Schema::hasColumn('kanban_tasks', 'ordem_servico_id')) {
            return;
        }

        Schema::table('kanban_tasks', function (Blueprint $table) {
            // Card da OS no Kanban do técnico: um por OS, some junto com a OS.
            $table->foreignId('ordem_servico_id')->nullable()->unique()->constrained('ordens_servico')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('kanban_tasks', 'ordem_servico_id')) {
            Schema::table('kanban_tasks', fn (Blueprint $table) => $table->dropConstrainedForeignId('ordem_servico_id'));
        }
    }
};
