<?php

namespace App\Models\Concerns;

use App\Models\Scopes\SecretariaScope;
use App\Services\Secretarias\ActiveSecretaria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Isola o model por secretaria ativa (coluna secretaria_id).
 *
 * Limite: DB::table(), SQL cru, join(), insert() e upsert() NÃO passam pelo escopo nem pelo
 * evento creating. Tabelas dos módulos devem ter secretaria_id NOT NULL + FK.
 */
trait BelongsToSecretaria
{
    public static function bootBelongsToSecretaria(): void
    {
        static::addGlobalScope(new SecretariaScope);

        static::creating(function ($model) {
            $active = app(ActiveSecretaria::class);

            // Fora de requisição HTTP (console/fila/seeder) não mexe no valor.
            if (! $active->inRequest()) {
                return;
            }

            // Rota pública (sem usuário/secretaria resolvida): só grava se o código do servidor já informou
            // a secretaria (ex.: pedido de cadastro). Sem valor, falha fechado.
            if (! $active->resolved()) {
                if (empty($model->secretaria_id)) {
                    throw ValidationException::withMessages(['secretaria_id' => 'secretaria nao resolvida']);
                }

                return;
            }

            if (! $active->isAll()) {
                if ($active->id() === null) {
                    throw ValidationException::withMessages(['secretaria_id' => 'secretaria nao resolvida']);
                }

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
