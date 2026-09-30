<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Cadastro/edição de profissional que recebe avisos da Vigilância por WhatsApp. */
class VigilanciaContatoWhatsappRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->profile === 'admin';
    }

    protected function prepareForValidation(): void
    {
        // Guarda só dígitos: aceita "(35) 99876-5432", "35 99876-5432" etc.
        if ($this->has('telefone')) {
            $this->merge(['telefone' => preg_replace('/\D+/', '', (string) $this->input('telefone'))]);
        }
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'telefone' => ['required', 'string', 'regex:/^\d{10,13}$/'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do profissional.',
            'telefone.required' => 'Informe o telefone com DDD.',
            'telefone.regex' => 'Telefone inválido: informe DDD + número (10 a 13 dígitos).',
        ];
    }
}
