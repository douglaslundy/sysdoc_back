<?php

namespace App\Services\Authorization;

use App\Models\AccessProfile;
use App\Models\ProfileSpecialityPermission;
use App\Models\User;

/**
 * Acesso às filas por especialidade. Definido no PERFIL de acesso (profile_speciality_permissions);
 * o usuário recebe a união dos perfis dele (principal + adicionais). Admin sempre tem acesso a tudo.
 */
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

        return ProfileSpecialityPermission::query()
            ->whereIn('access_profile_id', $this->profileIds($user))
            ->where('can_view', true)
            ->pluck('speciality_id')
            ->unique()
            ->values()
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

        return ProfileSpecialityPermission::query()
            ->whereIn('access_profile_id', $this->profileIds($user))
            ->get(['speciality_id', 'can_view', 'can_edit', 'can_insert'])
            ->groupBy('speciality_id')
            ->map(fn ($rows) => [
                'can_view' => $rows->contains(fn ($r) => (bool) $r->can_view),
                'can_edit' => $rows->contains(fn ($r) => (bool) $r->can_edit),
                'can_insert' => $rows->contains(fn ($r) => (bool) $r->can_insert),
            ])
            ->all();
    }

    private function hasFlag(User $user, int $specialityId, string $flag): bool
    {
        return ProfileSpecialityPermission::query()
            ->whereIn('access_profile_id', $this->profileIds($user))
            ->where('speciality_id', $specialityId)
            ->where($flag, true)
            ->exists();
    }

    /** Ids dos perfis ativos do usuário (principal + adicionais). */
    private function profileIds(User $user): array
    {
        return AccessProfile::query()
            ->whereIn('slug', $user->profileSlugs())
            ->where('ativo', true)
            ->pluck('id')
            ->all();
    }
}
