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
    public static function aplicar(): void
    {
        $id = Secretaria::where('slug', Secretaria::SAUDE_SLUG)->where('ativa', true)->value('id');

        app(ActiveSecretaria::class)->set($id ? (int) $id : null, false, $id ? [(int) $id] : []);
    }
}
