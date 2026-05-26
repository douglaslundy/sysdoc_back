<?php

namespace App\Observers;

use App\Models\Exame;
use App\Services\AuditService;

class ExameObserver
{
    public function created(Exame $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(Exame $model): void
    {
        $dirty = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(Exame $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
