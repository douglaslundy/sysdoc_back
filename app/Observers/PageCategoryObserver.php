<?php

namespace App\Observers;

use App\Models\PageCategory;
use App\Services\AuditService;

class PageCategoryObserver
{
    public function created(PageCategory $model): void
    {
        AuditService::record('CREATE', $model, null, $model->toArray());
    }

    public function updated(PageCategory $model): void
    {
        $dirty    = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        AuditService::record('UPDATE', $model, $original, $dirty);
    }

    public function deleted(PageCategory $model): void
    {
        AuditService::record('DELETE', $model, $model->toArray(), null);
    }
}
