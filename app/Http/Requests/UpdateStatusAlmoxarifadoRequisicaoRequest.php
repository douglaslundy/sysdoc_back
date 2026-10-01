<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusAlmoxarifadoRequisicaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:recebida,em_analise,aprovada,recusada,em_separacao,em_processo_de_entrega,entregue,cancelada'],
            'observacao' => ['nullable', 'string'],
        ];
    }
}
