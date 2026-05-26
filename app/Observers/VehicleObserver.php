<?php

namespace App\Observers;

use App\Models\Vehicle;
use App\Services\AuditService;

class VehicleObserver
{
    public function created(Vehicle $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(Vehicle $model): void
    {
        $dirty    = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(Vehicle $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
