<?php

namespace App\Observers;

use App\Models\MedicineItem;
use App\Services\AuditService;

class MedicineItemObserver
{
    public function created(MedicineItem $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(MedicineItem $model): void
    {
        $dirty    = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(MedicineItem $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
