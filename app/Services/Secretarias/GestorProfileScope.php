<?php

namespace App\Services\Secretarias;

use App\Models\AccessProfile;
use App\Models\Secretaria;
use App\Models\User;

/** Teto de um perfil criado por não-admin: páginas e capabilities do próprio perfil do ator (R11). */
class GestorProfileScope
{
    public const CAPABILITIES = [
        'chat_enabled',
        'almoxarifado_create_enabled',
        'almoxarifado_approve_enabled',
        'almoxarifado_deliver_enabled',
        'client_trips_view_enabled',
        'client_report_view_enabled',
        'client_history_view_enabled',
    ];

    /** Páginas do perfil do ator ∩ liberadas na secretaria ativa (páginas de gestão são isentas, como nas rotas). */
    public static function pageIds(User $actor): array
    {
        $own = AccessProfile::where('slug', $actor->profile)->first()?->pages()->get(['system_pages.id', 'system_pages.path']);
        if (! $own) {
            return [];
        }

        $ativa = app(ActiveSecretaria::class);
        $liberadas = ! $ativa->isAll() && $ativa->id()
            ? Secretaria::find($ativa->id())->pages()->pluck('system_pages.id')->all()
            : [];

        return $own->filter(fn ($p) => $ativa->isAll()
            || in_array($p->id, $liberadas, true)
            || in_array($p->path, Secretaria::PAGINAS_GESTAO, true))->pluck('id')->map(fn ($i) => (int) $i)->all();
    }

    /** @return array<string,bool> capability => o ator a possui */
    public static function capabilities(User $actor): array
    {
        $own = AccessProfile::where('slug', $actor->profile)->first();

        return collect(self::CAPABILITIES)->mapWithKeys(fn ($c) => [$c => (bool) ($own?->{$c})])->all();
    }
}
