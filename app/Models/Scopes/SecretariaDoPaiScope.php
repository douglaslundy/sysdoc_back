<?php

namespace App\Models\Scopes;

use App\Services\Secretarias\ActiveSecretaria;
use App\Support\ParticipacaoProtocolo;
use Illuminate\Support\Facades\Auth;
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
    /** @param  array{0: string, 1: string}|null  $avo [fk do pai para o avô, tabela do avô]: o pai também não tem coluna, a secretaria vem do avô. */
    public function __construct(private string $fk, private string $tabelaPai, private bool $participacao = false, private ?array $avo = null)
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

        $pai = DB::table($this->tabelaPai)->select('id')->where(function ($q) use ($active) {
            if ($this->avo) {
                $q->whereIn($this->tabelaPai.'.'.$this->avo[0], DB::table($this->avo[1])->select('id')->where('secretaria_id', $active->id()));
            } else {
                $q->where($this->tabelaPai.'.secretaria_id', $active->id());
            }
            // Protocolo entre secretarias: o destinatário vê o histórico do protocolo que recebeu.
            if ($this->participacao && ($uid = Auth::id())) {
                ParticipacaoProtocolo::aplicar($q, (int) $uid);
            }
        });
        $builder->whereIn($model->qualifyColumn($this->fk), $pai);
    }
}
