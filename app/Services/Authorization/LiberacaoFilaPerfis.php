<?php

namespace App\Services\Authorization;

use App\Models\AccessProfile;
use App\Models\ProfileSpecialityPermission;
use App\Models\Speciality;

/**
 * Especialidade marcada "liberar para todos os perfis com acesso às filas": todo perfil com a página
 * de Filas recebe ver/editar/inserir nela. Desmarcar remove a especialidade de todos os perfis.
 */
class LiberacaoFilaPerfis
{
    public const PAGINA_FILA = '/queue';

    private const TUDO = ['can_view' => true, 'can_edit' => true, 'can_insert' => true];

    public static function temFila(AccessProfile $perfil): bool
    {
        return $perfil->pages()->where('path', self::PAGINA_FILA)->exists();
    }

    /** Depois de salvar a especialidade: $antes = valor da caixa antes de salvar (false na criação). */
    public static function aposSalvarEspecialidade(Speciality $especialidade, bool $antes): void
    {
        if ($especialidade->liberar_perfis_fila) {
            $perfis = AccessProfile::whereHas('pages', fn ($q) => $q->where('path', self::PAGINA_FILA))->pluck('id');
            foreach ($perfis as $perfilId) {
                self::liberar((int) $perfilId, $especialidade->id);
            }
        } elseif ($antes) {
            ProfileSpecialityPermission::withoutGlobalScopes()->where('speciality_id', $especialidade->id)->delete();
        }
    }

    /** Perfil que passou a ter a página de Filas recebe as especialidades marcadas (de todas as secretarias). */
    public static function aposPerfilGanharFila(AccessProfile $perfil): void
    {
        $ids = Speciality::withoutGlobalScopes()->where('liberar_perfis_fila', true)->pluck('id');
        foreach ($ids as $id) {
            self::liberar($perfil->id, (int) $id);
        }
    }

    private static function liberar(int $perfilId, int $especialidadeId): void
    {
        ProfileSpecialityPermission::withoutGlobalScopes()->updateOrCreate(
            ['access_profile_id' => $perfilId, 'speciality_id' => $especialidadeId],
            self::TUDO,
        );
    }
}
