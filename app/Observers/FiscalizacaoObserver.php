<?php

namespace App\Observers;

use App\Models\Fiscalizacao;
use App\Models\KanbanTask;
use App\Services\AuditService;

class FiscalizacaoObserver
{
    public function created(Fiscalizacao $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(Fiscalizacao $model): void
    {
        $dirty = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(Fiscalizacao $model): void
    {
        // Fiscalizacao usa soft delete (o cascade do banco não dispara): o card da petição sai junto.
        KanbanTask::where('fiscalizacao_id', $model->id)->delete();

        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
