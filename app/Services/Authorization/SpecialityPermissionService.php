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

    /**
     * @return array<int, array{can_view: bool, can_edit: bool, can_insert: bool}> keyed by speciality_id.
     *         For admin, returns an empty array — callers must treat a missing key as "true for all three flags" when the user is admin.
     */
    public function permissionsFor(User $user): array
    {
        if ($user->profile === 'admin') {
            return [];
        }

        return UserSpecialityPermission::query()
            ->where('user_id', $user->id)
            ->get(['speciality_id', 'can_view', 'can_edit', 'can_insert'])
            ->keyBy('speciality_id')
            ->map(fn ($row) => [
                'can_view' => (bool) $row->can_view,
                'can_edit' => (bool) $row->can_edit,
                'can_insert' => (bool) $row->can_insert,
            ])
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
