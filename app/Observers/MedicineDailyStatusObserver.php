<?php

namespace App\Observers;

use App\Models\MedicineDailyStatus;
use App\Services\AuditService;

class MedicineDailyStatusObserver
{
    public function created(MedicineDailyStatus $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(MedicineDailyStatus $model): void
    {
        $dirty    = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(MedicineDailyStatus $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
