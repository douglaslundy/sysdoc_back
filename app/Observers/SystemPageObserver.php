<?php

namespace App\Observers;

use App\Models\SystemPage;
use App\Services\AuditService;

class SystemPageObserver
{
    public function created(SystemPage $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(SystemPage $model): void
    {
        $dirty    = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(SystemPage $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
