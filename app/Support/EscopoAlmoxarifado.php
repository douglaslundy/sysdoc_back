<?php

namespace App\Support;

use App\Services\Secretarias\ActiveSecretaria;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Secretaria dona dos registros do almoxarifado em criação/validação (ativa; admin em "Todas" informa). */
class EscopoAlmoxarifado
{
    /** @throws ValidationException 422 errors.secretaria_id quando não há secretaria (admin em "Todas" sem escolher). */
    public static function id(mixed $explicit = null): int
    {
        $id = app(ActiveSecretaria::class)->forCreate($explicit);

        if (! $id) {
            throw ValidationException::withMessages(['secretaria_id' => ['escolha a secretaria']]);
        }

        return $id;
    }

    /** Regra do campo secretaria_id no corpo: só o admin em "Todas" precisa (e pode) informar. */
    public static function regraCampo(): array
    {
        return app(ActiveSecretaria::class)->isAll()
            ? ['required', 'integer', Rule::exists('secretarias', 'id')->where('ativa', true)]
            : ['nullable'];
    }

    /** exists só entre registros da mesma secretaria (exists cru ignora o escopo global). */
    public static function existe(string $tabela, int $secretariaId): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists($tabela, 'id')->where('secretaria_id', $secretariaId);
    }

    public static function unico(string $tabela, string $coluna, int $secretariaId): \Illuminate\Validation\Rules\Unique
    {
        return Rule::unique($tabela, $coluna)->where('secretaria_id', $secretariaId);
    }
}
