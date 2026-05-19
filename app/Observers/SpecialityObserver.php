<?php

namespace App\Observers;

use App\Models\Speciality;
use App\Services\AuditService;

class SpecialityObserver
{
    public function created(Speciality $speciality): void
    {
        AuditService::record('CREATE', $speciality, null, $speciality->toArray());
    }

    public function updated(Speciality $speciality): void
    {
        $dirty = $speciality->getDirty();
        $original = array_intersect_key($speciality->getOriginal(), $dirty);
        AuditService::record('UPDATE', $speciality, $original, $dirty);
    }

    public function deleted(Speciality $speciality): void
    {
        AuditService::record('DELETE', $speciality, $speciality->toArray(), null);
    }
}
