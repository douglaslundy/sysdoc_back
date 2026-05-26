<?php

namespace App\Observers;

use App\Models\MedicoSolicitante;
use App\Services\AuditService;

class MedicoSolicitanteObserver
{
    public function created(MedicoSolicitante $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(MedicoSolicitante $model): void
    {
        $dirty = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(MedicoSolicitante $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
