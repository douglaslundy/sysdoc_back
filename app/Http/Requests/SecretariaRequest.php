<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SecretariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->profile === 'admin';
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:80', Rule::unique('secretarias', 'nome')->ignore($this->route('id'))],
            'ativa' => ['sometimes', 'boolean'],
            'page_ids' => ['sometimes', 'array'],
            'page_ids.*' => ['integer', 'exists:system_pages,id'],
        ];
    }
}
