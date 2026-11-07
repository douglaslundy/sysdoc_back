<?php

namespace App\Models\Concerns;

use App\Models\Scopes\SecretariaScope;
use App\Services\Secretarias\ActiveSecretaria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/** Isola o model por secretaria ativa (coluna secretaria_id). */
trait BelongsToSecretaria
{
    public static function bootBelongsToSecretaria(): void
    {
        static::addGlobalScope(new SecretariaScope);

        static::creating(function ($model) {
            $active = app(ActiveSecretaria::class);

            // Fora de requisição resolvida (console/fila/seeder) não mexe no valor.
            if (! $active->resolved()) {
                return;
            }

            if (! $active->isAll()) {
                // Quem não está em "todas" nunca grava em outra secretaria (ignora mass assignment).
                $model->secretaria_id = $active->id();

                return;
            }

            if (empty($model->secretaria_id)) {
                throw ValidationException::withMessages(['secretaria_id' => 'escolha a secretaria']);
            }
        });
    }

    /** Escape explícito (jobs, seeders): consulta sem o filtro de secretaria. */
    public static function withoutSecretaria(): Builder
    {
        return static::withoutGlobalScope(SecretariaScope::class);
    }
}
