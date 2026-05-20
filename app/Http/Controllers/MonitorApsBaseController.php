<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use PDO;

abstract class MonitorApsBaseController extends Controller
{
    protected function db(): \Illuminate\Database\ConnectionInterface
    {
        $row = DB::table('monitor_aps_configs')->first();

        $host     = $row->aps_db_host     ?? env('APS_DB_HOST', '');
        $port     = $row->aps_db_port     ?? env('APS_DB_PORT', 5432);
        $database = $row->aps_db_database ?? env('APS_DB_DATABASE', 'esus');
        $username = $row->aps_db_username ?? env('APS_DB_USERNAME', '');
        $password = $row->aps_db_password ?? env('APS_DB_PASSWORD', '');

        if ($password) {
            try { $password = decrypt($password); } catch (\Throwable) {}
        }

        config(['database.connections.pgsql_esus_runtime' => [
            'driver'          => 'pgsql',
            'host'            => $host,
            'port'            => (int) $port,
            'database'        => $database,
            'username'        => $username,
            'password'        => $password,
            'charset'         => 'utf8',
            'prefix'          => '',
            'schema'          => 'public',
            'sslmode'         => 'prefer',
            'options'         => [PDO::ATTR_TIMEOUT => 8],
        ]]);

        return DB::connection('pgsql_esus_runtime');
    }
}
