<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitorApsConfigController extends MonitorApsBaseController
{
    private function configPath(): string
    {
        return storage_path('app/monitor-aps-config.json');
    }

    private function loadConfig(): ?array
    {
        $path = $this->configPath();
        return file_exists($path) ? json_decode(file_get_contents($path), true) : null;
    }

    // GET /monitor-aps/config/status
    public function status()
    {
        $config = $this->loadConfig();
        $host   = $config['host']     ?? env('APS_DB_HOST', '');
        $banco  = $config['database'] ?? env('APS_DB_DATABASE', '');

        try {
            $this->db()->select('SELECT 1');
            return response()->json(['configured' => true, 'connected' => true, 'host' => $host, 'database' => $banco]);
        } catch (\Throwable $e) {
            return response()->json(['configured' => (bool) $host, 'connected' => false, 'error' => $e->getMessage()]);
        }
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

        config(['database.connections.pgsql_esus_test' => [
            'driver'   => 'pgsql',
            'host'     => $data['host'],
            'port'     => $data['port'] ?? 5432,
            'database' => $data['database'],
            'username' => $data['user'],
            'password' => $data['password'] ?? '',
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
            'sslmode'  => 'prefer',
        ]]);

        try {
            $equipes = DB::connection('pgsql_esus_test')->select(
                'SELECT COUNT(*) AS total FROM dim_equipe WHERE st_ativo = true'
            );
            return response()->json([
                'success'       => true,
                'total_equipes' => (int) ($equipes[0]->total ?? 0),
                'mensagem'      => 'Conexão estabelecida com sucesso.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'mensagem' => $e->getMessage()]);
        }
    }

    // POST /monitor-aps/config/save  (admin)
    public function save(Request $request)
    {
        $data = $request->validate([
            'host'     => 'required|string',
            'database' => 'required|string',
            'user'     => 'required|string',
            'port'     => 'nullable|integer',
            'password' => 'nullable|string',
        ]);

        try {
            file_put_contents($this->configPath(), json_encode([
                'host'     => $data['host'],
                'port'     => $data['port'] ?? 5432,
                'database' => $data['database'],
                'user'     => $data['user'],
                'password' => $data['password'] ?? '',
            ], JSON_PRETTY_PRINT));
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
