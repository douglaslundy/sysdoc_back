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

    /** Filtra consulta crua (DB::table/join), que ignora o escopo global, pela secretaria ativa. "Todas" não filtra. */
    public static function filtrar($query, string $coluna)
    {
        $active = app(ActiveSecretaria::class);

        if (! $active->inRequest() || $active->isAll()) {
            return $query;
        }

        return $active->id() === null ? $query->whereRaw('1=0') : $query->where($coluna, $active->id());
    }

    /**
     * Secretaria alvo das regras de validação: a do registro editado ($id; 404 se for de outra secretaria,
     * pois o find passa pelo escopo) ou a de criação (ativa, ou a informada pelo admin em "Todas").
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelo
     */
    public static function alvo(string $modelo, mixed $id, mixed $explicit = null): int
    {
        if ($id !== null) {
            return (int) ($modelo::find($id)?->secretaria_id ?? abort(404, 'Registro não encontrado.'));
        }

        return app(ActiveSecretaria::class)->forCreate($explicit) ?? 0;
    }

    /** exists em exame_campos (filha, sem coluna própria) só entre os campos de exames da secretaria. */
    public static function existeCampoDeExame(int $secretariaId): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('exame_campos', 'id')->where(
            fn ($q) => $q->whereIn('exame_id', \Illuminate\Support\Facades\DB::table('exames')->select('id')->where('secretaria_id', $secretariaId))
        );
    }

    /** exists restrito à secretaria ativa (filtros de listas); "Todas" não restringe. */
    public static function existeNaAtiva(string $tabela, string $coluna = 'id'): \Illuminate\Validation\Rules\Exists
    {
        $regra = Rule::exists($tabela, $coluna);
        $active = app(ActiveSecretaria::class);

        return $active->inRequest() && ! $active->isAll() ? $regra->where('secretaria_id', $active->id() ?? 0) : $regra;
    }
}
