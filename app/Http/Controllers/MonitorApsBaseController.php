<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

abstract class MonitorApsBaseController extends Controller
{
    protected function db(): \Illuminate\Database\ConnectionInterface
    {
        $path = storage_path('app/monitor-aps-config.json');
        if (file_exists($path)) {
            $c = json_decode(file_get_contents($path), true);
            config(['database.connections.pgsql_esus_runtime' => [
                'driver'   => 'pgsql',
                'host'     => $c['host'],
                'port'     => $c['port'] ?? 5432,
                'database' => $c['database'],
                'username' => $c['user'],
                'password' => $c['password'] ?? '',
                'charset'  => 'utf8',
                'prefix'   => '',
                'schema'   => 'public',
                'sslmode'  => 'prefer',
            ]]);
            return DB::connection('pgsql_esus_runtime');
        }
        return DB::connection('pgsql_esus');
    }
}
