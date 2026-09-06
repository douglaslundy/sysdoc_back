<?php

namespace App\Services\Authorization;

use Illuminate\Support\Facades\DB;

class UserSpecialityPermissionBackfiller
{
    /**
     * Concede acesso total (ver/editar/inserir) a todo usuário ativo que já tinha
     * acesso ao módulo de Fila, para todas as especialidades já cadastradas.
     * Idempotente: nunca duplica uma linha (user_id, speciality_id) já existente.
     *
     * @return int quantidade de linhas inseridas
     */
    public function run(): int
    {
        $specialityIds = DB::table('specialities')->pluck('id');

        if ($specialityIds->isEmpty()) {
            return 0;
        }

        $userIds = DB::table('users')
            ->where('active', true)
            ->where(function ($query) {
                $query->where('profile', 'admin')
                    ->orWhereIn('profile', function ($sub) {
                        $sub->select('access_profiles.slug')
                            ->from('access_profiles')
                            ->join('profile_page_permissions', 'profile_page_permissions.access_profile_id', '=', 'access_profiles.id')
                            ->join('system_pages', 'system_pages.id', '=', 'profile_page_permissions.system_page_id')
                            ->where('access_profiles.ativo', true)
                            ->where('system_pages.path', '/queue')
                            ->where('system_pages.ativo', true);
                    });
            })
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return 0;
        }

        $existing = DB::table('user_speciality_permissions')
            ->whereIn('user_id', $userIds)
            ->whereIn('speciality_id', $specialityIds)
            ->get(['user_id', 'speciality_id'])
            ->map(fn ($row) => $row->user_id.':'.$row->speciality_id)
            ->flip();

        $now = now();
        $rows = [];

        foreach ($userIds as $userId) {
            foreach ($specialityIds as $specialityId) {
                if (isset($existing[$userId.':'.$specialityId])) {
                    continue;
                }

                $rows[] = [
                    'user_id' => $userId,
                    'speciality_id' => $specialityId,
                    'can_view' => true,
                    'can_edit' => true,
                    'can_insert' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (empty($rows)) {
            return 0;
        }

        DB::table('user_speciality_permissions')->insert($rows);

        return count($rows);
    }
}
