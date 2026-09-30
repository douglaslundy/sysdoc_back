<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kanban_tasks')) {
            return;
        }

        Schema::table('kanban_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('kanban_tasks', 'unit_id')) {
                // Unidade responsável: só quem está lotada nela (ou acima) e o admin enxergam o card.
                $table->foreignId('unit_id')->nullable()->constrained('protocol_organizational_units')->nullOnDelete();
            }
            if (! Schema::hasColumn('kanban_tasks', 'fiscalizacao_id')) {
                $table->foreignId('fiscalizacao_id')->nullable()->unique()->constrained('fiscalizacoes')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('kanban_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('kanban_tasks', 'fiscalizacao_id')) {
                $table->dropConstrainedForeignId('fiscalizacao_id');
            }
            if (Schema::hasColumn('kanban_tasks', 'unit_id')) {
                $table->dropConstrainedForeignId('unit_id');
            }
        });
    }
};
