<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Models\UserSpecialityPermission;

class SpecialityPermissionService
{
    public function canView(User $user, int $specialityId): bool
    {
        if ($user->profile === 'admin') {
            return true;
        }

        return $this->hasFlag($user, $specialityId, 'can_view');
    }

    public function canEdit(User $user, int $specialityId): bool
    {
        if ($user->profile === 'admin') {
            return true;
        }

        return $this->hasFlag($user, $specialityId, 'can_edit');
    }

    public function canInsert(User $user, int $specialityId): bool
    {
        if ($user->profile === 'admin') {
            return true;
        }

        return $this->hasFlag($user, $specialityId, 'can_insert');
    }

    /**
     * @return array<int>|null null significa "sem restrição" (admin) — quem chamar não deve filtrar.
     */
    public function viewableSpecialityIds(User $user): ?array
    {
        if ($user->profile === 'admin') {
            return null;
        }

        return UserSpecialityPermission::query()
            ->where('user_id', $user->id)
            ->where('can_view', true)
            ->pluck('speciality_id')
            ->all();
    }

    private function hasFlag(User $user, int $specialityId, string $flag): bool
    {
        return UserSpecialityPermission::query()
            ->where('user_id', $user->id)
            ->where('speciality_id', $specialityId)
            ->where($flag, true)
            ->exists();
    }
}
