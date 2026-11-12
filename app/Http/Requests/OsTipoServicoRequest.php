<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OsTipoServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $criando = $this->isMethod('post');

        return [
            'secretaria_id' => [$criando ? 'required' : 'prohibited', 'integer', Rule::exists('secretarias', 'id')],
            'nome' => ['required', 'string', 'max:120'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
