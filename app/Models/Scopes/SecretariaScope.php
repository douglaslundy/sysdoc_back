<?php

namespace App\Models\Scopes;

use App\Services\Secretarias\ActiveSecretaria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SecretariaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $active = app(ActiveSecretaria::class);

        // Sem requisição autenticada resolvida (console, fila, seeder): não filtra.
        // Requisição resolvida sem secretaria: bloqueia tudo (fail closed).
        if (! $active->resolved() || $active->isAll()) {
            return;
        }

        if ($active->id() === null) {
            $builder->whereRaw('1=0');

            return;
        }

        $builder->where($model->qualifyColumn('secretaria_id'), $active->id());
    }
}
