<?php

namespace App\Observers;

use App\Models\Route;
use App\Services\AuditService;

class RouteObserver
{
    public function created(Route $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(Route $model): void
    {
        $dirty    = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(Route $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
