<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PDO;

abstract class MonitorApsBaseController extends Controller
{
    private ?object $apsConfigCache = null;
    private ?\Illuminate\Database\ConnectionInterface $apsConn = null;

    protected function apsConfig(): object
    {
        if ($this->apsConfigCache !== null) return $this->apsConfigCache;

        try {
            $row = DB::table('monitor_aps_configs')->first();
        } catch (\Throwable) {
            $row = null;
        }

        $this->apsConfigCache = (object) [
            'municipio_ibge' => $row?->municipio_ibge ?? env('MONITOR_APS_MUNICIPIO_IBGE', ''),
            'municipio_nome' => $row?->municipio_nome ?? env('MONITOR_APS_MUNICIPIO_NOME', ''),
            'estrato_ied'    => (int) ($row?->estrato_ied ?? env('MONITOR_APS_ESTRATO_IED', 4)),
        ];

        return $this->apsConfigCache;
    }

    protected function db(): \Illuminate\Database\ConnectionInterface
    {
        if ($this->apsConn !== null) return $this->apsConn;

        $row = Cache::remember('aps_db_config', 3600, function () {
            try { return DB::table('monitor_aps_configs')->first(); }
            catch (\Throwable) { return null; }
        });

        $host     = $row?->aps_db_host     ?? env('APS_DB_HOST', '');
        $port     = $row?->aps_db_port     ?? env('APS_DB_PORT', 5432);
        $database = $row?->aps_db_database ?? env('APS_DB_DATABASE', 'esus');
        $username = $row?->aps_db_username ?? env('APS_DB_USERNAME', '');
        $password = $row?->aps_db_password ?? env('APS_DB_PASSWORD', '');

        if ($password) {
            try { $password = decrypt($password); } catch (\Throwable) {}
        }

        config(['database.connections.pgsql_esus_runtime' => [
            'driver'   => 'pgsql',
            'host'     => $host,
            'port'     => (int) $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
            'sslmode'  => 'prefer',
            'options'  => [PDO::ATTR_TIMEOUT => 30],
        ]]);

        $this->apsConn = DB::connection('pgsql_esus_runtime');

        try {
            $this->apsConn->statement("SET statement_timeout = '25s'");
        } catch (\Throwable) {}

        return $this->apsConn;
    }

    /**
     * Verifica se uma coluna existe em uma tabela do banco eSUS.
     * Resultado cacheado por 24h — evita query repetida a cada request.
     */
    protected function hasColumn(string $table, string $column): bool
    {
        $key = "aps_col_{$table}_{$column}";
        return Cache::remember($key, 86400, function () use ($table, $column) {
            try {
                $row = $this->db()->selectOne("
                    SELECT 1 FROM information_schema.columns
                    WHERE table_schema = 'public'
                      AND table_name   = ?
                      AND column_name  = ?
                    LIMIT 1
                ", [$table, $column]);
                return $row !== null;
            } catch (\Throwable) {
                return false;
            }
        });
    }
}
