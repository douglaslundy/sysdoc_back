<?php

namespace App\Observers;

use App\Models\Alvara;
use App\Services\AuditService;

class AlvaraObserver
{
    public function created(Alvara $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(Alvara $model): void
    {
        $dirty    = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(Alvara $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
