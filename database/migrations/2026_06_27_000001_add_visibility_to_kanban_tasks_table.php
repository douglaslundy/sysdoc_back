<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kanban_tasks', function (Blueprint $table) {
            $table->string('visibility', 20)->default('public')->after('responsavel_id');
            $table->index(['visibility', 'created_by_id'], 'kanban_tasks_visibility_created_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('kanban_tasks', function (Blueprint $table) {
            $table->dropIndex('kanban_tasks_visibility_created_by_idx');
            $table->dropColumn('visibility');
        });
    }
};
