<?php

namespace App\Console\Commands;

use App\Models\ErrorLog;
use Illuminate\Console\Command;

class LimparErrorLogs extends Command
{
    protected $signature = 'logs:limpar-erros {--dias=90 : Dias de retenção dos registros de erro}';

    protected $description = 'Apaga registros de error_logs mais antigos que N dias (em lotes)';

    public function handle(): int
    {
        $limite = now()->subDays(max(1, (int) $this->option('dias')));
        $total = 0;

        do {
            $apagados = ErrorLog::where('created_at', '<', $limite)->limit(1000)->delete();
            $total += $apagados;
        } while ($apagados === 1000);

        $this->info("Apagados {$total} registros de erro anteriores a {$limite->toDateString()}.");

        return Command::SUCCESS;
    }
}
