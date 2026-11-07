<?php

namespace App\Http\Requests;

use App\Rules\PhoneValidation;
use App\Rules\ValidCpf;
use App\Services\Cadastro\CaptchaDesafio;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CadastroPublicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Remove caracteres de controle e colapsa espaços; valor não-string segue e cai na validação.
        $limpa = function ($v) {
            if (! is_string($v)) {
                return $v;
            }
            $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v) ?? '';

            return trim(preg_replace('/\s+/u', ' ', $v) ?? '');
        };
        $vazioNulo = fn ($v) => $v === '' ? null : $v;

        $preferido = $vazioNulo($limpa($this->input('preferred_name')));
        $phone = $this->input('phone');
        $phone = is_scalar($phone) ? preg_replace('/\D+/', '', (string) $phone) : null;
        $resposta = $this->input('captcha_resposta');

        $this->merge([
            'name' => $limpa($this->input('name')),
            'preferred_name' => is_string($preferido) ? mb_strtoupper($preferido, 'UTF-8') : $preferido,
            'email' => $limpa($this->input('email')),
            'cargo' => $limpa($this->input('cargo')),
            'observacao' => $vazioNulo($limpa($this->input('observacao'))),
            'phone' => $vazioNulo($phone),
            // Evita TypeError em validar(string, string) com null/array.
            'captcha_resposta' => is_scalar($resposta) ? (string) $resposta : '',
        ]);
    }

    public function rules(): array
    {
        return [
            'website' => ['prohibited'], // honeypot
            'name' => ['required', 'string', 'max:50'],
            'preferred_name' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', new PhoneValidation],
            'email' => ['required', 'string', 'email', 'max:100'],
            'cpf' => ['required', 'string', 'max:18', new ValidCpf()],
            'password' => ['required', 'string', 'min:8', 'confirmed',
                // bcrypt ignora/estoura além de 72 BYTES (multibyte conta mais que caracteres).
                fn ($attr, $valor, $fail) => strlen((string) $valor) > 72 ? $fail('A senha deve ter no máximo 72 bytes.') : null],
            'secretaria_id' => ['required', 'integer', Rule::exists('secretarias', 'id')->where('ativa', true)],
            'cargo' => ['required', 'string', 'max:80'],
            'observacao' => ['nullable', 'string', 'max:200'],
            'captcha_token' => ['required', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // Por último e só se o resto passou: captcha errado queima o token e nunca toca em contas.
        $validator->after(function (Validator $v) {
            if ($v->errors()->isEmpty()
                && ! app(CaptchaDesafio::class)->validar((string) $this->input('captcha_token'), (string) $this->input('captcha_resposta'))) {
                $v->errors()->add('captcha_resposta', 'Resposta incorreta ou expirada. Tente novamente.');
            }
        });
    }

    /** Só os campos esperados chegam ao serviço. */
    public function dadosCadastro(): array
    {
        return $this->safe()->only(['name', 'preferred_name', 'phone', 'email', 'cpf', 'password', 'secretaria_id', 'cargo', 'observacao']);
    }
}
