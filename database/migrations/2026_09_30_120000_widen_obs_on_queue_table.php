<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A baixa concatena a observacao antiga com a conclusao; com obs limitada a 200 caracteres
     * a gravacao falhava (422) e o item "nao saia da fila". Passa para 1000.
     */
    public function up(): void
    {
        if (Schema::hasTable('queue') && Schema::hasColumn('queue', 'obs') && DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE queue MODIFY obs VARCHAR(1000) NULL');
        }
    }

    public function down(): void
    {
        // Nao reduz de volta: poderia truncar observacoes ja gravadas.
    }
};
