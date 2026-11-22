<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OuvidoriaConsultaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['protocolo', 'senha'] as $campo) {
            $v = $this->input($campo);
            $this->merge([$campo => is_scalar($v) ? strtoupper(trim((string) $v)) : '']);
        }
    }

    public function rules(): array
    {
        return [
            'protocolo' => ['required', 'string', 'max:30'],
            'senha' => ['required', 'string', 'max:30'],
        ];
    }
}
