<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitorApsConfigController extends MonitorApsBaseController
{
    private function row(): ?object
    {
        try {
            return DB::table('monitor_aps_configs')->first();
        } catch (\Throwable) {
            return null;
        }
    }

    // GET /monitor-aps/config/status  — não tenta conectar ao PostgreSQL (rápido)
    public function status()
    {
        $row  = $this->row();
        $host = $row?->aps_db_host     ?? env('APS_DB_HOST', '');
        $port = $row?->aps_db_port     ?? env('APS_DB_PORT', 5432);
        $db   = $row?->aps_db_database ?? env('APS_DB_DATABASE', '');
        $user = $row?->aps_db_username ?? env('APS_DB_USERNAME', '');

        return response()->json([
            'configured' => (bool) $host,
            'connected'  => null,
            'host'       => $host,
            'port'       => $port,
            'database'   => $db,
            'user'       => $user,
        ]);
    }

    // GET /monitor-aps/config/load
    public function load()
    {
        $row = $this->row();

        $encrypted = $row ? ($row->aps_db_password ?? '') : '';
        $password  = '';
        if ($encrypted) {
            try { $password = decrypt($encrypted); } catch (\Throwable) { $password = $encrypted; }
        } elseif (env('APS_DB_PASSWORD', '')) {
            $password = env('APS_DB_PASSWORD', '');
        }

        return response()->json([
            'host'           => $row?->aps_db_host     ?? env('APS_DB_HOST',     ''),
            'port'           => $row?->aps_db_port     ?? env('APS_DB_PORT',     5432),
            'database'       => $row?->aps_db_database ?? env('APS_DB_DATABASE', 'esus'),
            'user'           => $row?->aps_db_username ?? env('APS_DB_USERNAME', ''),
            'password'       => $password,
            'municipio_ibge' => $row?->municipio_ibge  ?? env('MONITOR_APS_MUNICIPIO_IBGE', ''),
            'municipio_nome' => $row?->municipio_nome  ?? env('MONITOR_APS_MUNICIPIO_NOME', ''),
            'estrato_ied'    => (int) ($row?->estrato_ied ?? env('MONITOR_APS_ESTRATO_IED', 4)),
        ]);
    }

    // GET /monitor-aps/config/equipes
    public function equipes()
    {
        try {
            $rows = $this->db()->select("
                SELECT nu_ine, no_equipe,
                  CASE tp_equipe
                    WHEN 70 THEN 'eSF' WHEN 71 THEN 'eAP'
                    WHEN 72 THEN 'eSB' WHEN 80 THEN 'eMulti'
                    ELSE tp_equipe::text
                  END AS tipo,
                  nu_cnes, st_ativo
                FROM dim_equipe
                ORDER BY tp_equipe, no_equipe
            ");
            return response()->json(['equipes' => $rows]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // POST /monitor-aps/config/test  (admin)
    public function testar(Request $request)
    {
        $data = $request->validate([
            'host'     => 'required|string',
            'database' => 'required|string',
            'user'     => 'required|string',
            'port'     => 'nullable|integer',
            'password' => 'nullable|string',
        ]);

        $password = $data['password'] ?? '';
        if ($password === '') {
            $row = $this->row();
            $saved = $row?->aps_db_password ?? env('APS_DB_PASSWORD', '');
            if ($saved) {
                try { $password = decrypt($saved); } catch (\Throwable) { $password = $saved; }
            }
        }

        config(['database.connections.pgsql_esus_test' => [
            'driver'   => 'pgsql',
            'host'     => $data['host'],
            'port'     => $data['port'] ?? 5432,
            'database' => $data['database'],
            'username' => $data['user'],
            'password' => $password,
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
            'sslmode'  => 'prefer',
            'options'  => [\PDO::ATTR_TIMEOUT => 8],
        ]]);

        try {
            $conn = DB::connection('pgsql_esus_test');
            $conn->select('SELECT 1');

            $tabelas = $conn->select("
                SELECT table_schema AS schema, table_name AS tabela, table_type AS tipo
                FROM information_schema.tables
                WHERE table_schema NOT IN ('information_schema', 'pg_catalog')
                ORDER BY table_schema, table_name
            ");

            $nomes = collect($tabelas)->pluck('tabela');

            // Detecta schema DW (dim_equipe)
            $schemaDW = null;
            foreach ($tabelas as $t) {
                if ($t->tabela === 'dim_equipe') { $schemaDW = $t->schema; break; }
            }

            // Conta tabelas dim_ e fat_
            $qtdDim = $nomes->filter(fn($n) => str_starts_with($n, 'dim_'))->count();
            $qtdFat = $nomes->filter(fn($n) => str_starts_with($n, 'fat_'))->count();

            // Confirma se é banco eSUS PEC (tabelas operacionais conhecidas)
            $tabelasEsus = ['tb_cidadao', 'tb_atendimento_individual', 'tb_visita_domiciliar', 'tb_municipio'];
            $esusEncontradas = $nomes->filter(fn($n) => in_array($n, $tabelasEsus))->values()->toArray();

            $temDW = false;
            $totalEquipes = 0;
            if ($schemaDW) {
                try {
                    $result = $conn->select("SELECT COUNT(*) AS total FROM \"{$schemaDW}\".dim_equipe WHERE st_ativo = true");
                    $temDW = true;
                    $totalEquipes = (int) ($result[0]->total ?? 0);
                } catch (\Throwable) {}
            }

            $schemas = collect($tabelas)->pluck('schema')->unique()->values()->toArray();

            if ($temDW) {
                $mensagem = "Conectado — schema DW: {$schemaDW} | {$totalEquipes} equipe(s) ativa(s) | " . count($tabelas) . " tabela(s).";
            } elseif ($qtdDim > 0 || $qtdFat > 0) {
                $mensagem = "Conectado — tabelas DW encontradas (dim: {$qtdDim}, fat: {$qtdFat}) mas dim_equipe ausente. Verifique o schema.";
            } elseif (!empty($esusEncontradas)) {
                $mensagem = "Conectado ao banco eSUS PEC operacional (" . implode(', ', $esusEncontradas) . "). O módulo DW não está habilitado — ative o Data Warehouse no eSUS PEC para usar o Monitor APS.";
            } else {
                $mensagem = "Conectado — " . count($tabelas) . " tabela(s) encontrada(s), mas não parece ser um banco eSUS PEC. Verifique as configurações.";
            }

            return response()->json([
                'success'        => true,
                'tem_dw'         => $temDW,
                'schema_dw'      => $schemaDW,
                'schemas'        => $schemas,
                'total_equipes'  => $totalEquipes,
                'qtd_dim'        => $qtdDim,
                'qtd_fat'        => $qtdFat,
                'esus_confirmado' => !empty($esusEncontradas),
                'tabelas'        => $tabelas,
                'mensagem'       => $mensagem,
            ]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $msg = preg_replace('/\s*\(Connection:.*?\)/i', '', $msg);
            $msg = preg_replace('/\s*\(SQL:.*\)/is', '', $msg);
            $msg = preg_replace('/^SQLSTATE\[\w+\]\s*\[\d+\]\s*/i', '', $msg);
            return response()->json(['success' => false, 'mensagem' => trim($msg)]);
        }
    }

    // POST /monitor-aps/config/save  (admin)
    public function save(Request $request)
    {
        $data = $request->validate([
            'host'           => 'required|string',
            'database'       => 'required|string',
            'user'           => 'required|string',
            'port'           => 'nullable|integer',
            'password'       => 'nullable|string',
            'municipio_ibge' => 'nullable|string',
            'municipio_nome' => 'nullable|string',
            'estrato_ied'    => 'nullable|integer|min:1|max:4',
        ]);

        $payload = [
            'aps_db_host'     => $data['host'],
            'aps_db_port'     => $data['port'] ?? 5432,
            'aps_db_database' => $data['database'],
            'aps_db_username' => $data['user'],
            'municipio_ibge'  => $data['municipio_ibge'] ?? '',
            'municipio_nome'  => $data['municipio_nome'] ?? '',
            'estrato_ied'     => $data['estrato_ied'] ?? 4,
            'updated_at'      => now(),
        ];

        if (!empty($data['password'])) {
            $payload['aps_db_password'] = encrypt($data['password']);
        }

        try {
            $existing = DB::table('monitor_aps_configs')->first();
            if ($existing) {
                DB::table('monitor_aps_configs')->where('id', $existing->id)->update($payload);
            } else {
                $payload['created_at'] = now();
                DB::table('monitor_aps_configs')->insert($payload);
            }
            DB::purge('pgsql_esus_runtime');
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // GET /monitor-aps/config/explorar  (admin)
    public function explorar()
    {
        try {
            $conn = $this->db();

            // Todas as tabelas tb_* com contagem de registros
            $tabelasTb = $conn->select("
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = 'public'
                  AND table_name LIKE 'tb_%'
                ORDER BY table_name
            ");

            $tabelas = [];
            foreach ($tabelasTb as $t) {
                $nome = $t->table_name;

                // Colunas
                $colunas = $conn->select("
                    SELECT column_name AS coluna, data_type AS tipo, is_nullable AS nulo
                    FROM information_schema.columns
                    WHERE table_schema = 'public' AND table_name = ?
                    ORDER BY ordinal_position
                ", [$nome]);

                // Contagem (com timeout implícito do PDO)
                $total = null;
                try {
                    $r = $conn->select("SELECT COUNT(*) AS total FROM \"{$nome}\"");
                    $total = (int) ($r[0]->total ?? 0);
                } catch (\Throwable) {}

                $tabelas[] = [
                    'tabela'  => $nome,
                    'total'   => $total,
                    'colunas' => $colunas,
                ];
            }

            return response()->json([
                'success' => true,
                'total'   => count($tabelas),
                'tabelas' => $tabelas,
            ]);
        } catch (\Throwable $e) {
            $msg = preg_replace('/\s*\(Connection:.*?\)/i', '', $e->getMessage());
            return response()->json(['success' => false, 'error' => trim($msg)], 500);
        }
    }
}
