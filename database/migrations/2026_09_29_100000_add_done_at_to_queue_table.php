<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('queue') || Schema::hasColumn('queue', 'done_at')) {
            return;
        }

        Schema::table('queue', function (Blueprint $table) {
            // Momento em que o item recebeu baixa (done = 1).
            $table->dateTime('done_at')->nullable()->after('date_of_realized');
            $table->index(['done', 'done_at', 'id'], 'queue_done_at_index');
        });

        // Registros antigos nao guardavam o momento da baixa: o melhor dado disponivel
        // e a ultima atualizacao do registro.
        DB::table('queue')
            ->where('done', 1)
            ->whereNull('done_at')
            ->update(['done_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('queue') || ! Schema::hasColumn('queue', 'done_at')) {
            return;
        }

        Schema::table('queue', function (Blueprint $table) {
            $table->dropIndex('queue_done_at_index');
            $table->dropColumn('done_at');
        });
    }
};
