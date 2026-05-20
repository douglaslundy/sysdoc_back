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
        $host = $row->aps_db_host     ?? env('APS_DB_HOST', '');
        $port = $row->aps_db_port     ?? env('APS_DB_PORT', 5432);
        $db   = $row->aps_db_database ?? env('APS_DB_DATABASE', '');
        $user = $row->aps_db_username ?? env('APS_DB_USERNAME', '');

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
        return response()->json([
            'host'           => $row->aps_db_host     ?? env('APS_DB_HOST',     ''),
            'port'           => $row->aps_db_port     ?? env('APS_DB_PORT',     5432),
            'database'       => $row->aps_db_database ?? env('APS_DB_DATABASE', 'esus'),
            'user'           => $row->aps_db_username ?? env('APS_DB_USERNAME', ''),
            'municipio_ibge' => $row->municipio_ibge  ?? env('MONITOR_APS_MUNICIPIO_IBGE', ''),
            'municipio_nome' => $row->municipio_nome  ?? env('MONITOR_APS_MUNICIPIO_NOME', ''),
            'estrato_ied'    => (int) ($row->estrato_ied ?? env('MONITOR_APS_ESTRATO_IED', 4)),
            'has_password'   => !empty($row ? ($row->aps_db_password ?? '') : env('APS_DB_PASSWORD', '')),
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
            $saved = $row->aps_db_password ?? env('APS_DB_PASSWORD', '');
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
            $result = DB::connection('pgsql_esus_test')->select(
                'SELECT COUNT(*) AS total FROM dim_equipe WHERE st_ativo = true'
            );
            return response()->json([
                'success'       => true,
                'total_equipes' => (int) ($result[0]->total ?? 0),
                'mensagem'      => 'Conexão estabelecida com sucesso.',
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
}
