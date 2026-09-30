<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsultaDenunciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'protocolo' => ['required', 'string', 'max:30'],
            'senha' => ['required', 'string', 'max:30'],
        ];
    }
}
