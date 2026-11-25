<?php

namespace App\Observers;

use App\Models\KanbanTask;
use App\Models\OrdemServico;

/** Mantém o card da OS no Kanban desde a abertura: técnico (quando houver) como responsável e a situação da OS. */
class OrdemServicoObserver
{
    private const STATUS = [
        'aberta' => 'novo',
        'atribuida' => 'novo',
        'em_atendimento' => 'em_andamento',
        'concluida' => 'concluido',
        'cancelada' => 'concluido',
    ];

    public function saved(OrdemServico $os): void
    {
        if (! $os->wasRecentlyCreated && ! $os->wasChanged(['tecnico_id', 'status', 'secretaria_id', 'prazo', 'prioridade', 'local', 'descricao'])) {
            return;
        }

        $this->sincronizar($os);
    }

    /** Cria ou atualiza o card da OS (também usado pela migration que cria os cards das OS antigas). */
    public function sincronizar(OrdemServico $os): void
    {
        $card = KanbanTask::withoutSecretaria()->firstWhere('ordem_servico_id', $os->id);

        $card ??=new KanbanTask(['ordem_servico_id' => $os->id, 'visibility' => 'public', 'ordem' => 0, 'created_by_id' => $os->solicitante_id]);
        $status = self::STATUS[$os->status] ?? 'novo';

        // forceFill + saveQuietly: o card fica na secretaria de destino da OS, não na secretaria ativa de quem abriu.
        $card->forceFill([
            'secretaria_id' => $os->secretaria_id,
            'responsavel_id' => $os->tecnico_id,
            'titulo' => mb_substr('OS '.$os->numero.' - '.$os->local, 0, 200),
            'descricao' => $os->descricao,
            'status' => $status,
            'prioridade' => $os->prioridade ?: 'normal',
            'vencimento' => $os->prazo,
            'concluido_at' => $status === 'concluido' ? ($card->concluido_at ?? now()) : null,
        ])->saveQuietly();
    }
}
