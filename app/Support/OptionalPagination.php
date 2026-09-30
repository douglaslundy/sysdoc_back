<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\Builder as EloquentContract;
use Illuminate\Contracts\Database\Query\Builder as QueryContract;
use Illuminate\Http\Request;

/**
 * Paginação opcional das listagens:
 *  - com `page` (e opcional `per_page`, no máximo `pagination.max_per_page`) devolve o paginador
 *    do Laravel (`data`, `total`, `current_page`, `last_page`...);
 *  - sem `page` mantém a lista simples de antes (clientes antigos, como o app móvel), porém
 *    limitada às `pagination.legacy_cap` linhas mais recentes do ordenamento da consulta,
 *    para que nenhuma tela carregue a tabela inteira.
 */
class OptionalPagination
{
    public static function apply(EloquentContract|QueryContract $query, Request $request, int $defaultPerPage = 25)
    {
        if ($request->has('page')) {
            $max = (int) config('pagination.max_per_page', 100);
            $perPage = max(1, min($max, (int) $request->query('per_page', $defaultPerPage)));

            return $query->paginate($perPage);
        }

        return $query->limit((int) config('pagination.legacy_cap', 500))->get();
    }
}
