<?php

namespace Tests\Concerns;

use App\Models\AccessProfile;
use App\Models\SystemPage;

/**
 * Concede páginas a um perfil (Perfis > páginas liberadas) nos testes. Como os usuários reais de
 * cada tela têm a página do módulo, os testes que exercitam o endpoint precisam refletir isso.
 */
trait GrantsPages
{
    protected function grantPages(string $profileSlug, array $paths): void
    {
        $profile = AccessProfile::firstOrCreate(['slug' => $profileSlug], ['nome' => $profileSlug, 'ativo' => true]);

        $ids = collect($paths)->map(
            fn (string $path) => SystemPage::firstOrCreate(['path' => $path], ['titulo' => $path, 'ativo' => true])->id
        )->all();

        $profile->pages()->syncWithoutDetaching($ids);
    }
}
