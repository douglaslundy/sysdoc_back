<?php

namespace App\Observers;

use App\Models\Estabelecimento;
use App\Services\AuditService;

class EstabelecimentoObserver
{
    public function created(Estabelecimento $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(Estabelecimento $model): void
    {
        $dirty    = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(Estabelecimento $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
