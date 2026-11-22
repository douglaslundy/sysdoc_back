<?php

namespace App\Services\Ouvidoria;

use App\Models\OuvidoriaManifestacao;
use App\Services\Secretarias\ActiveSecretaria;
use Illuminate\Database\Eloquent\Builder;

/**
 * Única porta de leitura das manifestações na área interna. O escopo automático por secretaria é
 * desligado (withoutSecretaria) porque a origem de um encaminhamento continua vendo o caso em leitura.
 */
class OuvidoriaVisibilidade
{
    public function consulta(): Builder
    {
        $q = OuvidoriaManifestacao::withoutSecretaria();
        $ativa = app(ActiveSecretaria::class);

        // Fora de requisição HTTP (console, fila) e admin em "todas": sem filtro.
        if (! $ativa->inRequest() || $ativa->isAll()) {
            return $q;
        }
        if ($ativa->id() === null) {
            return $q->whereRaw('1=0');
        }

        $id = $ativa->id();

        return $q->where(fn (Builder $w) => $w->where('secretaria_id', $id)
            ->orWhereIn('id', fn ($s) => $s->select('manifestacao_id')->from('ouvidoria_acessos_leitura')->where('secretaria_id', $id)));
    }

    /** Escrita (responder, status, encaminhar): só a secretaria dona; a origem em leitura não age. */
    public function podeAgir(OuvidoriaManifestacao $m): bool
    {
        $ativa = app(ActiveSecretaria::class);
        if (! $ativa->inRequest() || $ativa->isAll()) {
            return true;
        }

        return $ativa->id() !== null && (int) $m->secretaria_id === $ativa->id();
    }
}
