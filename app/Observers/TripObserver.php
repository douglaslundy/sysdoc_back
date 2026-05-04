<?php

namespace App\Observers;

use App\Models\Trip;
use App\Services\AuditService;

class TripObserver
{
    public function created(Trip $trip): void
    {
        AuditService::record('CREATE', $trip, null, $trip->toArray());
    }

    public function updated(Trip $trip): void
    {
        $dirty    = $trip->getDirty();
        $original = array_intersect_key($trip->getOriginal(), $dirty);
        AuditService::record('UPDATE', $trip, $original, $dirty);
    }

    public function deleted(Trip $trip): void
    {
        AuditService::record('DELETE', $trip, $trip->toArray(), null);
    }
}
