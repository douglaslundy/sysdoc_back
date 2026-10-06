<?php

namespace App\Console\Commands;

use App\Models\MessageLog;
use Illuminate\Console\Command;

class LimparMessageLogs extends Command
{
    protected $signature = 'mensagens:limpar-logs {--dias=180 : Dias de retenção do histórico de mensagens enviadas}';

    protected $description = 'Apaga registros de message_logs mais antigos que N dias (em lotes)';

    public function handle(): int
    {
        $limite = now()->subDays(max(1, (int) $this->option('dias')));
        $total = 0;

        do {
            $apagados = MessageLog::where('created_at', '<', $limite)->limit(1000)->delete();
            $total += $apagados;
        } while ($apagados === 1000);

        $this->info("Apagadas {$total} mensagens anteriores a {$limite->toDateString()}.");

        return Command::SUCCESS;
    }
}
