<?php

namespace App\Http\Requests;

use App\Models\OuvidoriaManifestacao;
use App\Rules\PhoneValidation;
use App\Rules\ValidCpf;
use App\Services\Cadastro\CaptchaDesafio;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OuvidoriaPublicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Remove formatação invisível e caracteres de controle; valor não-string segue e cai na validação.
        $limpa = function ($v, bool $multilinha = false) {
            if (! is_string($v)) {
                return $v;
            }
            $v = preg_replace('/\p{Cf}+/u', '', $v) ?? '';
            $v = $multilinha
                ? (preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $v) ?? '')
                : (preg_replace('/[\x00-\x1F\x7F\p{Zl}\p{Zp}]+/u', ' ', $v) ?? '');

            return trim($multilinha ? $v : (preg_replace('/\s+/u', ' ', $v) ?? ''));
        };
        $vazioNulo = fn ($v) => $v === '' ? null : $v;
        $phone = $this->input('phone');
        $phone = is_scalar($phone) ? preg_replace('/\D+/', '', (string) $phone) : null;
        $resposta = $this->input('captcha_resposta');

        $this->merge([
            'assunto' => $limpa($this->input('assunto')),
            'descricao' => $limpa($this->input('descricao'), true),
            'nome' => $vazioNulo($limpa($this->input('nome'))),
            'email' => $vazioNulo($limpa($this->input('email'))),
            'phone' => $vazioNulo($phone),
            'cpf' => $vazioNulo($limpa($this->input('cpf'))),
            'identificar' => filter_var($this->input('identificar'), FILTER_VALIDATE_BOOLEAN),
            'captcha_resposta' => is_scalar($resposta) ? (string) $resposta : '',
        ]);
    }

    public function rules(): array
    {
        return [
            'website' => ['prohibited'], // honeypot
            'secretaria_id' => ['required', 'integer', Rule::exists('secretarias', 'id')->where('ativa', true)->where('ouvidoria_publica', true)],
            'tipo' => ['required', Rule::in(array_keys(OuvidoriaManifestacao::TIPOS))],
            'assunto' => ['required', 'string', 'min:3', 'max:160'],
            'descricao' => ['required', 'string', 'min:10', 'max:5000'],
            'identificar' => ['boolean'],
            'nome' => ['required_if:identificar,true', 'nullable', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'email', 'max:160'],
            'phone' => ['nullable', new PhoneValidation],
            'cpf' => ['nullable', 'string', 'max:18', new ValidCpf()],
            'anexos' => ['nullable', 'array', 'max:3'],
            'anexos.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120'],
            'captcha_token' => ['required', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->boolean('identificar') && ! $this->filled('email') && ! $this->filled('phone')) {
                $v->errors()->add('email', 'Informe e-mail ou telefone para contato.');
            }
            // Por último e só se o resto passou: captcha errado queima o token.
            if ($v->errors()->isEmpty()
                && ! app(CaptchaDesafio::class)->validar((string) $this->input('captcha_token'), (string) $this->input('captcha_resposta'))) {
                $v->errors()->add('captcha_resposta', 'Resposta incorreta ou expirada. Tente novamente.');
            }
        });
    }

    /** Anônimo nunca leva dado pessoal, mesmo que o corpo o envie. */
    public function dadosManifestacao(): array
    {
        $dados = $this->safe()->only(['secretaria_id', 'tipo', 'assunto', 'descricao']);
        $identificado = $this->boolean('identificar');
        $dados['anonimo'] = ! $identificado;
        foreach (['nome', 'email', 'phone', 'cpf'] as $campo) {
            $dados[$campo] = $identificado ? $this->input($campo) : null;
        }

        return $dados;
    }
}
