<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        // PDO::ATTR_TIMEOUT não controla timeout de conexão no pgsql — apenas de statement.
        // fsockopen com 5s de timeout detecta host inacessível rapidamente e evita que
        // o PHP estoure max_execution_time (que derruba os headers CORS junto).
        if ($host) {
            $socket = @fsockopen($host, (int) $port, $errno, $errstr, 3.0);
            if ($socket === false) {
                throw new \RuntimeException("eSUS PEC inacessível ({$host}:{$port}): {$errstr}");
            }
            fclose($socket);
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
            'options'  => [PDO::ATTR_TIMEOUT => 10],
        ]]);

        $this->apsConn = DB::connection('pgsql_esus_runtime');

        try {
            $this->apsConn->statement("SET statement_timeout = '25s'");
        } catch (\Throwable $e) {
            // SET statement_timeout só falha se a conexão/autenticação falhou.
            // Propagamos para que o caller receba 503 em vez de uma conexão quebrada
            // que produziria "Erro ao consultar o banco de dados" em toda query seguinte.
            throw new \RuntimeException('Falha ao conectar ao banco eSUS PEC: ' . $e->getMessage(), 0, $e);
        }

        return $this->apsConn;
    }

    /**
     * Versão do schema — incrementada em MonitorApsConfigController::save()
     * para invalidar caches de hasColumn/hasTable sem precisar de cache tags.
     */
    protected function schemaCacheVersion(): int
    {
        return (int) Cache::get('aps_schema_v', 0);
    }

    /**
     * Verifica se uma tabela existe no schema public do banco eSUS.
     * Usa pg_catalog — independente de versão do PostgreSQL.
     * Resultado cacheado por 24h com invalidação pela versão do schema.
     */
    protected function hasTable(string $table): bool
    {
        $v   = $this->schemaCacheVersion();
        $key = "aps_table2_{$v}_{$table}";
        return Cache::remember($key, 86400, function () use ($table) {
            try {
                $row = $this->db()->selectOne("
                    SELECT 1
                    FROM pg_catalog.pg_class c
                    JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                    WHERE c.relname = ?
                      AND c.relkind = 'r'
                      AND n.nspname = 'public'
                    LIMIT 1
                ", [$table]);
                return $row !== null;
            } catch (\Throwable) {
                return false;
            }
        });
    }

    /**
     * Retorna o primeiro nome de coluna da lista que existir na tabela dada.
     * Útil para lidar com variações de schema entre versões do eSUS PEC.
     */
    protected function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $col) {
            if ($this->hasColumn($table, $col)) {
                return $col;
            }
        }
        return null;
    }

    /**
     * Verifica se uma coluna existe em uma tabela do banco eSUS.
     * Usa pg_catalog.pg_attribute — funciona em qualquer versão do PostgreSQL,
     * sem depender de information_schema (que pode falhar em versões antigas).
     * Resultado cacheado por 24h; chave inclui versão do schema para invalidação.
     */
    protected function hasColumn(string $table, string $column): bool
    {
        $v   = $this->schemaCacheVersion();
        $key = "aps_col2_{$v}_{$table}_{$column}";
        return Cache::remember($key, 86400, function () use ($table, $column) {
            try {
                $row = $this->db()->selectOne("
                    SELECT 1 FROM pg_catalog.pg_attribute
                    WHERE attrelid = ?::regclass
                      AND attname   = ?
                      AND attnum    > 0
                      AND NOT attisdropped
                    LIMIT 1
                ", [$table, $column]);
                return $row !== null;
            } catch (\Throwable) {
                return false;
            }
        });
    }

    /**
     * Retorna null (sem restrição) ou array de INEs permitidos para o usuário.
     * Definido pelo middleware EnsureEquipeAps.
     */
    protected function resolveAllowedInes(Request $request): ?array
    {
        return $request->attributes->get('_ines_permitidos');
    }

    /**
     * Aborta com 403 se o INE requisitado não estiver na lista de INEs permitidos.
     * Não faz nada se a lista for null (sem restrição) ou se $ine for null.
     */
    protected function assertIneAllowed(Request $request, ?string $ine): void
    {
        if ($ine === null) return;
        $allowed = $this->resolveAllowedInes($request);
        if ($allowed !== null && !in_array($ine, $allowed, true)) {
            abort(403, 'Equipe não autorizada.');
        }
    }

    /**
     * Retorna o fragmento WHERE + bindings para filtrar por INE(s).
     *
     * Casos:
     *   - $ine preenchido: filtra exatamente por esse INE.
     *   - $ine null + $allowedInes null: sem filtro (retorna ['', []]).
     *   - $ine null + $allowedInes []: WHERE 1=0 (RT sem equipes = sem resultados).
     *   - $ine null + $allowedInes ['A','B']: WHERE $column IN ('A','B').
     *
     * @param string $column  Nome da coluna INE na query (ex: 'e.nu_ine' ou 'nu_ine').
     */
    protected function buildIneWhere(?string $ine, ?array $allowedInes, string $column = 'nu_ine'): array
    {
        if ($ine !== null) {
            return ["{$column} = ?", [$ine]];
        }

        if ($allowedInes === null) {
            return ['', []];
        }

        if (empty($allowedInes)) {
            return ['1=0', []];
        }

        $placeholders = implode(',', array_fill(0, count($allowedInes), '?'));
        return ["{$column} IN ({$placeholders})", $allowedInes];
    }

    /**
     * Retorna um sufixo para chave de cache que previne colisão entre
     * usuários com restrições diferentes de equipe.
     */
    protected function cacheRestrictSuffix(?array $allowedInes): string
    {
        if ($allowedInes === null) return '';
        if (empty($allowedInes)) return '_r_empty';
        return '_r' . substr(md5(implode(',', $allowedInes)), 0, 8);
    }
}
