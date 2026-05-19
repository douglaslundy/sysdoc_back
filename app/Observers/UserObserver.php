<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditService;

class UserObserver
{
    public function created(User $user): void
    {
        AuditService::record('CREATE', $user, null, $user->toArray());
    }

    public function updated(User $user): void
    {
        $dirty = $user->getDirty();
        $original = array_intersect_key($user->getOriginal(), $dirty);
        AuditService::record('UPDATE', $user, $original, $dirty);
    }

    public function deleted(User $user): void
    {
        AuditService::record('DELETE', $user, $user->toArray(), null);
    }
}
