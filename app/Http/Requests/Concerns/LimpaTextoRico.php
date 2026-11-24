<?php

namespace App\Http\Requests\Concerns;

use App\Services\Texto\HtmlSeguro;

/** Para FormRequests: limpa o HTML dos campos de texto rico antes de validar (validated() e input() já veem o limpo). */
trait LimpaTextoRico
{
    /** @param  array<int, string>  $campos  chaves com ponto são aceitas (ex. "kanban.descricao") */
    protected function limparTextoRico(array $campos): void
    {
        $dados = $this->all();
        $tocados = [];
        foreach ($campos as $campo) {
            $valor = data_get($dados, $campo);
            if (is_string($valor)) {
                data_set($dados, $campo, HtmlSeguro::limpar($valor));
                $tocados[] = explode('.', $campo)[0];
            }
        }
        if ($tocados) {
            $this->merge(array_intersect_key($dados, array_flip($tocados)));
        }
    }
}
