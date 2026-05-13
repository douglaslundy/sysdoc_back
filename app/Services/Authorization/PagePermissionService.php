<?php

namespace App\Services\Authorization;

use App\Models\AccessProfile;
use App\Models\User;

class PagePermissionService
{
    public function canAccess(User $user, string $path): bool
    {
        if ($user->profile === 'admin') {
            return true;
        }

        return AccessProfile::query()
            ->where('slug', $user->profile)
            ->where('ativo', true)
            ->whereHas('pages', fn ($q) => $q->where('path', $path)->where('ativo', true))
            ->exists();
    }

    public function canAccessAny(User $user, array $paths): bool
    {
        if ($user->profile === 'admin') {
            return true;
        }

        return AccessProfile::query()
            ->where('slug', $user->profile)
            ->where('ativo', true)
            ->whereHas('pages', fn ($q) => $q->whereIn('path', $paths)->where('ativo', true))
            ->exists();
    }
}
