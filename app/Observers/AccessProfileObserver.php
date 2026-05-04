<?php

namespace App\Observers;

use App\Models\AccessProfile;
use App\Services\AuditService;

class AccessProfileObserver
{
    public function created(AccessProfile $profile): void
    {
        AuditService::record('CREATE', $profile, null, $profile->toArray());
    }

    public function updated(AccessProfile $profile): void
    {
        $dirty    = $profile->getDirty();
        $original = array_intersect_key($profile->getOriginal(), $dirty);
        AuditService::record('UPDATE', $profile, $original, $dirty);
    }

    public function deleted(AccessProfile $profile): void
    {
        AuditService::record('DELETE', $profile, $profile->toArray(), null);
    }
}
