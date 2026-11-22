<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OuvidoriaEncaminharRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'secretaria_id' => ['required', 'integer', 'exists:secretarias,id'],
            'observacao' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
