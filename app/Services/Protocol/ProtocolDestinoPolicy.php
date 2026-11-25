<?php

namespace App\Services\Protocol;

use App\Models\ProtocolUserUnit;
use App\Models\User;

/** Destinos de protocolo permitidos pelo perfil do autor (ver User::protocolDestinoUnitIds). */
class ProtocolDestinoPolicy
{
    public const MSG_UNIDADE = 'Você não pode enviar protocolo para este destino.';

    public const MSG_PESSOA = 'Você não pode enviar protocolo para esta pessoa.';

    /** null = permitido; senão ['campo' => ..., 'mensagem' => ...]. */
    public static function negar(User $autor, ?int $unitId, ?int $destinoUserId): ?array
    {
        $permitidas = $autor->protocolDestinoUnitIds();
        if ($permitidas === null) {
            return null;
        }
        if ($unitId !== null && ! in_array($unitId, $permitidas, true)) {
            return ['campo' => 'destino_unit_id', 'mensagem' => self::MSG_UNIDADE];
        }
        if ($destinoUserId !== null && ! self::lotadoEm($destinoUserId, $permitidas)) {
            return ['campo' => 'destino_user_id', 'mensagem' => self::MSG_PESSOA];
        }

        return null;
    }

    public static function pessoaPermitida(User $autor, int $userId): bool
    {
        $permitidas = $autor->protocolDestinoUnitIds();

        return $permitidas === null || self::lotadoEm($userId, $permitidas);
    }

    private static function lotadoEm(int $userId, array $unitIds): bool
    {
        return ProtocolUserUnit::withoutGlobalScopes()->where('user_id', $userId)->where('ativo', true)
            ->whereIn('protocol_organizational_unit_id', $unitIds)->exists();
    }
}
