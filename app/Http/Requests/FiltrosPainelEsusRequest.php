<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FiltrosPainelEsusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cnes'        => 'required|string|max:20',
            'data'        => 'nullable|date_format:Y-m-d',
            'data_inicio' => 'nullable|date_format:Y-m-d',
            'data_fim'    => 'nullable|date_format:Y-m-d',
            'equipe'      => 'nullable|integer',
        ];
    }
}
