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

        // "/modulo*" = qualquer página abaixo de /modulo (respeita a fronteira "/").
        $exact = array_values(array_filter($paths, fn ($p) => ! str_ends_with($p, '*')));
        $prefixes = array_map(fn ($p) => rtrim(substr($p, 0, -1), '/'), array_filter($paths, fn ($p) => str_ends_with($p, '*')));

        return AccessProfile::query()
            ->where('slug', $user->profile)
            ->where('ativo', true)
            ->whereHas('pages', function ($q) use ($exact, $prefixes) {
                $q->where('ativo', true)->where(function ($w) use ($exact, $prefixes) {
                    if ($exact) {
                        $w->orWhereIn('path', $exact);
                    }
                    foreach ($prefixes as $prefix) {
                        $w->orWhere('path', $prefix)->orWhere('path', 'like', $prefix.'/%');
                    }
                });
            })
            ->exists();
    }
}
