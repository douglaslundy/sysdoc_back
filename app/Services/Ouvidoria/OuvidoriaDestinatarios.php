<?php

namespace App\Services\Ouvidoria;

use App\Models\Secretaria;
use App\Models\User;
use Database\Seeders\OuvidoriaSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Quem pode receber um encaminhamento: usuário ativo com a página da ouvidoria, numa secretaria que a libera. */
class OuvidoriaDestinatarios
{
    /** @var string[]|null */
    private ?array $slugs = null;

    /** Perfis que têm a página da ouvidoria. */
    private function slugsComPagina(): array
    {
        return $this->slugs ??= DB::table('access_profiles')
            ->join('profile_page_permissions as pp', 'pp.access_profile_id', '=', 'access_profiles.id')
            ->join('system_pages as sp', 'sp.id', '=', 'pp.system_page_id')
            ->where('sp.path', OuvidoriaSeeder::PAGINA)->where('access_profiles.ativo', true)
            ->pluck('access_profiles.slug')->all();
    }

    private function elegivel(User $u): bool
    {
        return (bool) $u->active && ($u->profile === 'admin' || in_array($u->profile, $this->slugsComPagina(), true));
    }

    /** @return Collection<int, User> */
    public function elegiveis(): Collection
    {
        return User::where('active', true)
            ->where(fn ($q) => $q->where('profile', 'admin')->orWhereIn('profile', $this->slugsComPagina()))
            ->with('secretarias:id,nome')->orderBy('name')->get();
    }

    public function valido(User $destino, int $secretariaId): bool
    {
        if (! $this->elegivel($destino)) {
            return false;
        }
        $secretaria = Secretaria::where('id', $secretariaId)->where('ativa', true)->first();
        if (! $secretaria) {
            return false;
        }
        if ($destino->profile === 'admin') {
            return true;
        }

        return $destino->secretarias()->where('secretarias.id', $secretariaId)->exists()
            && $secretaria->pages()->where('path', OuvidoriaSeeder::PAGINA)->exists();
    }

    /** Secretarias em que o usuário pode receber (para o seletor do front). */
    public function secretariasDe(User $u): Collection
    {
        $candidatas = $u->profile === 'admin' ? Secretaria::where('ativa', true)->get(['id', 'nome']) : $u->secretarias;

        return $candidatas->filter(fn ($s) => $this->valido($u, $s->id))->map(fn ($s) => ['id' => $s->id, 'nome' => $s->nome])->values();
    }
}
