<?php

namespace App\Console\Commands;

use App\Models\KanbanTask;
use Illuminate\Console\Command;

class ArchivarKanbanConcluidas extends Command
{
    protected $signature = 'kanban:arquivar-concluidas {--dias=90 : Dias após conclusão para arquivar}';

    protected $description = 'Arquiva tarefas do kanban concluídas há mais de N dias';

    public function handle(): int
    {
        $dias = (int) $this->option('dias');
        $limite = now()->subDays($dias);

        $count = KanbanTask::whereNotNull('concluido_at')
            ->where('concluido_at', '<', $limite)
            ->whereNull('arquivado_at')
            ->update(['arquivado_at' => now()]);

        $this->info("Arquivadas {$count} tarefas concluídas há mais de {$dias} dias.");

        return Command::SUCCESS;
    }
}
