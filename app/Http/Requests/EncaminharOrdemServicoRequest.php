<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EncaminharOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $secretaria = (int) $this->input('secretaria_id');

        return [
            'secretaria_id' => ['required', 'integer', Rule::exists('secretarias', 'id')->where('ativa', true)],
            'tipo_servico_id' => ['required', 'integer', Rule::exists('os_tipos_servico', 'id')->where('secretaria_id', $secretaria)->where('ativo', true)],
            'tecnico_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('profile', 'tecnico')->where('active', true)],
            'observacao' => ['nullable', 'string', 'max:500'],
        ];
    }
}
