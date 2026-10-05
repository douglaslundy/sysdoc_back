<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Status único de protocolo concluído: "encerrado". Mantém encerrado_em coerente.
        DB::table('protocols')
            ->where('status', 'concluido')
            ->update([
                'status' => 'encerrado',
                'encerrado_em' => DB::raw('COALESCE(encerrado_em, updated_at)'),
            ]);
    }

    public function down(): void
    {
        // Irreversível: não há como distinguir os que eram "concluido" dos "encerrado" originais.
    }
};
