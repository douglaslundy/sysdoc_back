<?php

namespace App\Observers;

use App\Models\CategoriaExame;
use App\Services\AuditService;

class CategoriaExameObserver
{
    public function created(CategoriaExame $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(CategoriaExame $model): void
    {
        $dirty = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(CategoriaExame $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
