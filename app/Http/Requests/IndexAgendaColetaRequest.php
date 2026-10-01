<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexAgendaColetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => 'nullable|date',
            'inicio' => 'nullable|date',
            'fim' => 'nullable|date',
        ];
    }
}
