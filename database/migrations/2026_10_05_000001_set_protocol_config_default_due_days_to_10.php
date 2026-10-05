<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Prazo padrão passou de 5 para 10 dias; atualiza só quem ainda usa o valor antigo.
        DB::table('protocol_configs')->where('default_due_days', 5)->update(['default_due_days' => 10]);
    }

    public function down(): void
    {
        DB::table('protocol_configs')->where('default_due_days', 10)->update(['default_due_days' => 5]);
    }
};
