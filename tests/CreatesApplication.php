<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $this->ensureTestingDatabaseExists();

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    private function ensureTestingDatabaseExists(): void
    {
        $connection = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? null);
        if ($connection !== 'mysql') {
            return;
        }

        $database = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? null);
        $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
        $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');
        $username = getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? 'root');
        $password = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');

        if (!$database) {
            return;
        }

        try {
            $pdo = new \PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                $username,
                $password,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]
            );

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            // If the environment blocks CREATE DATABASE, keep default behavior.
        }
    }
}
