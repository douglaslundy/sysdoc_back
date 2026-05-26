<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function download(): StreamedResponse
    {
        $dbName   = config('database.connections.mysql.database');
        $fileName = 'sysdoc-backup-' . now()->format('Y-m-d_H-i-s') . '.sql';

        return response()->streamDownload(function () use ($dbName) {
            set_time_limit(0);
            ini_set('memory_limit', '512M');

            $pdo = DB::connection()->getPdo();

            $this->writeHeader($dbName);

            $tables = DB::select(
                "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
                 ORDER BY TABLE_NAME",
                [$dbName]
            );

            foreach ($tables as $tableRow) {
                $this->dumpTable($tableRow->TABLE_NAME, $pdo);
                $this->safeFlush();
            }

            echo "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        }, $fileName, [
            'Content-Type'      => 'application/octet-stream',
            'Cache-Control'     => 'no-store, no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function writeHeader(string $dbName): void
    {
        echo "-- Sysdoc Database Backup\n";
        echo "-- Gerado em: " . now()->format('Y-m-d H:i:s') . "\n";
        echo "-- Banco: {$dbName}\n";
        echo "-- -----------------------------------------------\n\n";
        echo "SET NAMES utf8mb4;\n";
        echo "SET CHARACTER_SET_CLIENT = utf8mb4;\n";
        echo "SET CHARACTER_SET_RESULTS = utf8mb4;\n";
        echo "SET COLLATION_CONNECTION = 'utf8mb4_unicode_ci';\n";
        echo "SET FOREIGN_KEY_CHECKS = 0;\n";
        echo "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        echo "SET time_zone = '+00:00';\n\n";
    }

    private function dumpTable(string $table, \PDO $pdo): void
    {
        echo "\n-- ---\n";
        echo "-- Tabela: `{$table}`\n";
        echo "-- ---\n\n";
        echo "DROP TABLE IF EXISTS `{$table}`;\n";

        $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
        $createSql    = $createResult[0]->{'Create Table'};
        echo $createSql . ";\n";

        $this->dumpRows($table, $pdo);
    }

    private function dumpRows(string $table, \PDO $pdo): void
    {
        $offset    = 0;
        $batchSize = 500;

        do {
            $rows = DB::table($table)->offset($offset)->limit($batchSize)->get();

            if ($rows->isEmpty()) {
                break;
            }

            $columns    = array_keys((array) $rows->first());
            $columnList = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

            $valueBlocks = $rows->map(function ($row) use ($pdo) {
                $escaped = array_map(function ($val) use ($pdo) {
                    return $val === null ? 'NULL' : $pdo->quote((string) $val);
                }, (array) $row);

                return '(' . implode(', ', $escaped) . ')';
            })->implode(",\n");

            echo "\nINSERT INTO `{$table}` ({$columnList}) VALUES\n{$valueBlocks};\n";

            $offset += $batchSize;
            $this->safeFlush();
        } while ($rows->count() === $batchSize);
    }

    private function safeFlush(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
