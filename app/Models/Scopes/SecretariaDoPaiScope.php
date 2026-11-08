<?php

namespace App\Models\Scopes;

use App\Services\Secretarias\ActiveSecretaria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

/**
 * Tabela filha (anexo, histórico, comentário, visualização...) sem secretaria_id própria: herda a do pai.
 * Mesma regra do SecretariaScope (console/fila abertos; HTTP sem secretaria fecha; "Todas" vê tudo).
 */
class SecretariaDoPaiScope implements Scope
{
    public function __construct(private string $fk, private string $tabelaPai)
    {
    }

    public function apply(Builder $builder, Model $model): void
    {
        $active = app(ActiveSecretaria::class);

        if (! $active->inRequest() || $active->isAll()) {
            return;
        }

        if ($active->id() === null) {
            $builder->whereRaw('1=0');

            return;
        }

        $builder->whereIn(
            $model->qualifyColumn($this->fk),
            DB::table($this->tabelaPai)->select('id')->where('secretaria_id', $active->id())
        );
    }
}
