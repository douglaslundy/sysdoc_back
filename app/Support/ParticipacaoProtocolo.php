<?php

namespace App\Support;

use App\Models\Protocol;
use Illuminate\Support\Facades\DB;

/**
 * Protocolo entre secretarias: quem recebe (usuário destino, membro da unidade destino) ou já recebeu encaminhamento
 * enxerga SÓ esse protocolo, mesmo sendo de outra secretaria. Nada além dele é aberto.
 */
class ParticipacaoProtocolo
{
    /** Acrescenta (OR) as condições de participação a uma consulta de `protocols` (Eloquent ou DB::table). */
    public static function aplicar($q, int $userId): void
    {
        // Só protocolos de secretarias em que o usuário NÃO está vinculado (nas dele, vale trocar a ativa).
        $q->orWhere(fn ($p) => $p
            ->whereNotIn('protocols.secretaria_id', DB::table('user_secretarias')->select('secretaria_id')->where('user_id', $userId))
            ->where(fn ($w) => $w
                ->where('protocols.responsavel_atual_id', $userId)
                // Lotação só conta se a unidade é de uma secretaria do próprio usuário (ex-membro não conta).
                ->orWhereIn(
                    'protocols.destino_unit_id',
                    DB::table('protocol_user_units as pu')->join('protocol_organizational_units as pun', 'pun.id', '=', 'pu.protocol_organizational_unit_id')
                        ->select('pu.protocol_organizational_unit_id')->where('pu.user_id', $userId)->where('pu.ativo', true)
                        ->whereIn('pun.secretaria_id', DB::table('user_secretarias')->select('secretaria_id')->where('user_id', $userId))
                )
                ->orWhereExists(fn ($s) => $s->select(DB::raw(1))->from('protocol_movements as pm')
                    ->whereColumn('pm.protocol_id', 'protocols.id')
                    // Só quem recebeu de fora da secretaria (marca gravada em ProtocolMovement); criar/comentar ou ser
                    // destinatário dentro da secretaria do protocolo (ex-membro) não conta.
                    ->where('pm.dados->destinatario_externo', true)
                    ->where('pm.to_user_id', $userId))));
    }

    /** Decisão pontual para um protocolo já carregado (usada nos alertas). */
    public static function participa(Protocol $protocol, int $userId): bool
    {
        return Protocol::withoutGlobalScopes()->whereKey($protocol->id)
            ->where(fn ($q) => self::aplicar($q->whereRaw('1=0'), $userId))->exists();
    }
}
