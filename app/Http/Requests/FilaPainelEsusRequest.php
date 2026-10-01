<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilaPainelEsusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cnes'         => 'required|string|max:20',
            'equipe'       => 'nullable|integer',
            'profissional' => 'nullable|integer',
            'data'         => 'nullable|date_format:Y-m-d',
            'data_inicio'  => 'nullable|date_format:Y-m-d',
            'data_fim'     => 'nullable|date_format:Y-m-d',
            'situacao'     => 'nullable|in:aguardando,atendidos,nao_aguardaram',
        ];
    }
}
