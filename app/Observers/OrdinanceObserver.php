<?php

namespace App\Observers;

use App\Models\Ordinance;
use App\Services\AuditService;

class OrdinanceObserver
{
    public function created(Ordinance $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(Ordinance $model): void
    {
        $dirty    = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(Ordinance $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
