<?php

namespace App\Http\Requests;

class PublicMedicineDailyRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.date' => 'O parâmetro date deve estar em formato de data válido.',
        ];
    }
}
