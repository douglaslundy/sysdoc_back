<?php

namespace App\Observers;

use App\Models\VigilanciaConfig;
use App\Services\AuditService;

class VigilanciaConfigObserver
{
    public function created(VigilanciaConfig $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(VigilanciaConfig $model): void
    {
        $dirty    = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(VigilanciaConfig $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
