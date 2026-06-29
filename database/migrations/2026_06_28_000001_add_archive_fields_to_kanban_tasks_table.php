<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kanban_tasks', function (Blueprint $table) {
            $table->timestamp('concluido_at')->nullable()->after('vencimento');
            $table->timestamp('arquivado_at')->nullable()->after('concluido_at');
            $table->index('concluido_at', 'kanban_concluido_at_idx');
            $table->index('arquivado_at', 'kanban_arquivado_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('kanban_tasks', function (Blueprint $table) {
            $table->dropIndex('kanban_concluido_at_idx');
            $table->dropIndex('kanban_arquivado_at_idx');
            $table->dropColumn(['concluido_at', 'arquivado_at']);
        });
    }
};
