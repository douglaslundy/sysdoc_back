<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OuvidoriaStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // "respondida" só pelo endpoint de resposta; "encaminhada" só pelo de encaminhamento.
            'status' => ['required', Rule::in(['em_analise', 'encerrada', 'arquivada'])],
            'observacao' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
