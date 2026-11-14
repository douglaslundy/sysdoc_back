<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Lotação em unidade só vale enquanto o usuário tem vínculo com a secretaria da unidade. */
class LotacaoPorSecretaria
{
    /** Inativa as lotações de unidades de secretarias de que o usuário não faz (mais) parte. Admin não tem vínculos: fica de fora. */
    public static function inativarForaDosVinculos(User $user): void
    {
        if ($user->profile === 'admin') {
            return;
        }

        DB::table('protocol_user_units')->where('user_id', $user->id)->where('ativo', true)
            ->whereIn('protocol_organizational_unit_id', DB::table('protocol_organizational_units')->select('id')
                ->whereNotIn('secretaria_id', DB::table('user_secretarias')->select('secretaria_id')->where('user_id', $user->id)))
            ->update(['ativo' => false, 'updated_at' => now()]);
    }

    /** @return int[] ids das unidades ativas do usuário que pertencem a secretarias dele (admin: todas as dele). */
    public static function unidadesValidas(User $user): array
    {
        $q = DB::table('protocol_user_units as pu')->join('protocol_organizational_units as u', 'u.id', '=', 'pu.protocol_organizational_unit_id')
            ->where('pu.user_id', $user->id)->where('pu.ativo', true);
        if ($user->profile !== 'admin') {
            $q->whereIn('u.secretaria_id', DB::table('user_secretarias')->select('secretaria_id')->where('user_id', $user->id));
        }

        return $q->pluck('u.id')->map(fn ($i) => (int) $i)->all();
    }
}
