<?php

namespace App\Http\Requests;

class PublicMedicineMonthlyRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['nullable', 'regex:/^\d{4}\-(0[1-9]|1[0-2])$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'month.regex' => 'O parâmetro month deve estar no formato AAAA-MM.',
        ];
    }
}
