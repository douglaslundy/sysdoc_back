<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => trim((string) $this->input('nome')),
            'email' => trim((string) $this->input('email')),
            'telefone' => trim((string) $this->input('telefone')),
            'mensagem' => trim((string) $this->input('mensagem')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'telefone' => ['required', 'string', 'max:20', 'regex:/^[\d\s()+\-.]{8,20}$/'],
            'mensagem' => ['required', 'string', 'max:500'],
            // Honeypot: humanos não preenchem; bots sim.
            'website' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe seu nome.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'telefone.required' => 'Informe seu telefone.',
            'telefone.regex' => 'Informe um telefone válido, com DDD.',
            'mensagem.required' => 'Escreva sua mensagem.',
            'mensagem.max' => 'A mensagem deve ter no máximo 500 caracteres.',
        ];
    }
}
