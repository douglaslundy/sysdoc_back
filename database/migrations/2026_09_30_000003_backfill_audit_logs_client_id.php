<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CHUNK = 5000;

    /**
     * Liga registros antigos de auditoria ao cidadao quando isso pode ser derivado do proprio
     * registro (cadastro, fila, pedido de exame). Idempotente: so toca linhas sem client_id.
     * Em lotes por faixa de id para nao segurar bloqueio longo em tabelas grandes.
     */
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs') || ! Schema::hasColumn('audit_logs', 'client_id')) {
            return;
        }

        $maxId = (int) DB::table('audit_logs')->max('id');
        if ($maxId === 0) {
            return;
        }

        for ($from = 1; $from <= $maxId; $from += self::CHUNK) {
            $to = $from + self::CHUNK - 1;

            $this->link('Client', 'clients', 'c.id', $from, $to);
            $this->link('Queue', 'queue', 'src.id_client', $from, $to);
            $this->link('PedidoExame', 'pedidos_exame', 'src.client_id', $from, $to);
        }
    }

    public function down(): void
    {
        // Preenchimento retroativo: nada a desfazer.
    }

    private function link(string $modelType, string $table, string $clientColumn, int $from, int $to): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $alias = $table === 'clients' ? 'c' : 'src';

        DB::update(
            "UPDATE audit_logs a JOIN {$table} {$alias} ON {$alias}.id = a.model_id
             SET a.client_id = {$clientColumn}
             WHERE a.model_type = ? AND a.client_id IS NULL AND a.id BETWEEN ? AND ?",
            [$modelType, $from, $to]
        );
    }
};
