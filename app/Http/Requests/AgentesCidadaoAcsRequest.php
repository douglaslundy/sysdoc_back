<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentesCidadaoAcsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ine' => 'nullable|string',
        ];
    }
}
