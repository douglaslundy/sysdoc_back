<?php

namespace App\Support;

use App\Models\Secretaria;
use App\Services\Secretarias\ActiveSecretaria;

/**
 * Rotas públicas (sem login) de farmácia e laboratório leem SEMPRE da Saúde.
 * Fixa a secretaria ativa da requisição no servidor; nunca aceita secretaria_id do cliente.
 * Sem a Saúde cadastrada, fica sem secretaria e as consultas voltam vazias (falha fechado).
 */
class SaudePublica
{
    /** Vigilância pública (denúncia, consulta de protocolo, motivos): a secretaria dona vem da config (padrão Saúde). */
    public static function vigilancia(): void
    {
        self::aplicar((string) config('vigilancia.secretaria_slug', Secretaria::SAUDE_SLUG));
    }

    /** Painel público da fila (/showPublicQueue): a secretaria dona vem da config (padrão Saúde). */
    public static function fila(): void
    {
        self::aplicar((string) config('fila.secretaria_slug', Secretaria::SAUDE_SLUG));
    }

    /** Rota pública que identifica o registro (ex.: UUID da fila): a requisição passa a valer na secretaria do registro. */
    public static function de(int $secretariaId): void
    {
        app(ActiveSecretaria::class)->set($secretariaId, false, [$secretariaId]);
    }

    public static function aplicar(?string $slug = null): void
    {
        $id = Secretaria::where('slug', $slug ?: Secretaria::SAUDE_SLUG)->where('ativa', true)->value('id');

        app(ActiveSecretaria::class)->set($id ? (int) $id : null, false, $id ? [(int) $id] : []);
    }
}
