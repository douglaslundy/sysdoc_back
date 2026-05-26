<?php

namespace App\Observers;

use App\Models\Letter;
use App\Services\AuditService;

class LetterObserver
{
    public function created(Letter $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(Letter $model): void
    {
        $dirty    = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(Letter $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
