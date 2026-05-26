<?php

namespace App\Observers;

use App\Models\MedicineMonthlyAcquisition;
use App\Services\AuditService;

class MedicineMonthlyAcquisitionObserver
{
    public function created(MedicineMonthlyAcquisition $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(MedicineMonthlyAcquisition $model): void
    {
        $dirty    = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(MedicineMonthlyAcquisition $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
