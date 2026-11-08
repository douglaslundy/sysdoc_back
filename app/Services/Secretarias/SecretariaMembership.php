<?php

namespace App\Services\Secretarias;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Quem pode ser responsável/destinatário/assinante de um registro de uma secretaria. */
class SecretariaMembership
{
    /** Usuário ativo, admin ou vinculado à secretaria. */
    public static function atende(?int $userId, ?int $secretariaId): bool
    {
        if (! $userId || ! $secretariaId) {
            return false;
        }

        $user = User::query()->where('active', true)->find($userId);

        return $user !== null && ($user->profile === 'admin'
            || DB::table('user_secretarias')->where('user_id', $userId)->where('secretaria_id', $secretariaId)->exists());
    }

    /** Filtra uma consulta de usuários para os que atendem a secretaria (admin sempre entra). */
    public static function filtrar($query, int $secretariaId)
    {
        // Sem secretaria resolvida (id 0): ninguém, nem admin (falha fechado).
        if ($secretariaId <= 0) {
            return $query->whereRaw('1=0');
        }

        return $query->where(fn ($q) => $q->where('profile', 'admin')
            ->orWhereIn('id', DB::table('user_secretarias')->select('user_id')->where('secretaria_id', $secretariaId)));
    }
}
