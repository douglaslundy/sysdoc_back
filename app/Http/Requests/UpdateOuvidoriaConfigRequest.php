<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOuvidoriaConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Itens em branco somem antes da validação (a tela pode mandar linha vazia). */
    protected function prepareForValidation(): void
    {
        foreach (['registrar', 'consultar'] as $secao) {
            $dados = $this->input($secao);
            if (is_array($dados) && is_array($dados['itens'] ?? null)) {
                $dados['itens'] = array_values(array_filter(
                    array_map(fn ($i) => is_string($i) ? trim($i) : $i, $dados['itens']),
                    fn ($i) => $i !== '' && $i !== null
                ));
                $this->merge([$secao => $dados]);
            }
        }
    }

    public function rules(): array
    {
        $regras = ['nome_marca' => 'required|string|max:60'];
        foreach (['registrar', 'consultar'] as $secao) {
            $regras["$secao"] = 'required|array';
            $regras["$secao.titulo"] = 'required|string|max:120';
            $regras["$secao.texto"] = 'nullable|string|max:500';
            $regras["$secao.itens"] = 'present|array|max:10';
            $regras["$secao.itens.*"] = 'string|max:160';
        }

        return $regras;
    }
}
