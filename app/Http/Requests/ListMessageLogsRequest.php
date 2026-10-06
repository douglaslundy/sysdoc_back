<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListMessageLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'canal' => ['nullable', 'in:whatsapp,email'],
            'busca' => ['nullable', 'string', 'max:100'],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', 'after_or_equal:de'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
