<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecusarCadastroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $motivo = $this->input('motivo');
        if (is_string($motivo)) {
            // Mesma limpeza do pedido público: sem caracteres de controle, espaços colapsados.
            $motivo = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $motivo) ?? '';
            $this->merge(['motivo' => trim(preg_replace('/\s+/u', ' ', $motivo) ?? '')]);
        }
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
