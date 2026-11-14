<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Protocolo de pedido de cadastro (tem dados pessoais) e tudo que pende dele (card, movimentações,
 * comentários, anexos, notificações, visualizações): só admin e gestor vinculado à secretaria do
 * pedido enxergam. Sem usuário autenticado (console, fila, rota pública) não enxerga: falha fechado.
 * Quem precisa ler por dentro (fluxo de aprovação) usa withoutGlobalScope(CadastroProtocoloScope::class).
 *
 * $protocolFk null = o próprio model é o protocolo; senão, a coluna que aponta para protocols.id.
 */
class CadastroProtocoloScope implements Scope
{
    public const TIPO = 'solicitacao_cadastro';

    public function __construct(private ?string $protocolFk = null)
    {
    }

    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user?->profile === 'admin') {
            return;
        }

        if ($this->protocolFk === null) {
            $builder->where(function ($q) use ($model, $user) {
                $q->where($model->qualifyColumn('tipo'), '<>', self::TIPO);
                if ($user?->profile === 'gestor') {
                    $q->orWhereIn($model->qualifyColumn('secretaria_id'), self::secretariasDe($user));
                }
            });

            return;
        }

        $fk = $model->qualifyColumn($this->protocolFk);
        $cadastro = fn (QueryBuilder $s) => $s->select(DB::raw(1))->from('protocols as pc')
            ->whereColumn('pc.id', $fk)->where('pc.tipo', self::TIPO);

        $builder->where(function ($q) use ($cadastro, $user) {
            $q->whereNotExists($cadastro);
            if ($user?->profile === 'gestor') {
                $q->orWhereExists(fn ($s) => $cadastro($s)->whereIn('pc.secretaria_id', self::secretariasDe($user)));
            }
        });
    }

    /** Decisão pontual (show/update etc.) com a mesma regra do escopo. */
    public static function pode(?User $user, ?int $secretariaId): bool
    {
        if ($user?->profile === 'admin') {
            return true;
        }

        return $user?->profile === 'gestor'
            && $secretariaId !== null
            && self::secretariasDe($user)->where('secretaria_id', $secretariaId)->exists();
    }

    public static function secretariasDe(User $user): QueryBuilder
    {
        return DB::table('user_secretarias')->select('secretaria_id')->where('user_id', $user->id);
    }
}
