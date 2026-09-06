<?php

namespace App\Observers;

use App\Models\Fiscalizacao;
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
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
