<?php

use App\Models\KanbanTask;
use App\Models\OrdemServico;
use App\Observers\OrdemServicoObserver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** OS abertas antes do card na abertura ficam sem card: cria o das que ainda não foram encerradas. */
    public function up(): void
    {
        if (! Schema::hasColumn('kanban_tasks', 'ordem_servico_id')) {
            return;
        }

        $comCard = KanbanTask::withoutGlobalScopes()->whereNotNull('ordem_servico_id')->pluck('ordem_servico_id');
        $observer = new OrdemServicoObserver;

        OrdemServico::withoutGlobalScopes()->whereNotIn('status', OrdemServico::ENCERRADAS)->whereNotIn('id', $comCard)
            ->orderBy('id')->each(fn (OrdemServico $os) => $observer->sincronizar($os));
    }

    public function down(): void
    {
        // Os cards criados aqui são iguais aos criados pelo sistema; não há como distingui-los.
    }
};
