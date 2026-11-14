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

        // Fora de requisição HTTP (console, fila, seeder): não filtra.
        // Requisição HTTP sem secretaria (não resolvida ou zero vínculos): bloqueia tudo (fail closed).
        if (! $active->inRequest() || $active->isAll()) {
            return;
        }

        if ($active->id() === null) {
            $builder->whereRaw('1=0');

            return;
        }

        $builder->where($model->qualifyColumn('secretaria_id'), $active->id());
    }
}
