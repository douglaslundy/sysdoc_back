<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConcluirOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'relato' => ['required', 'string', 'max:5000'],
            'materiais' => ['nullable', 'string', 'max:3000'],
            'tempo_gasto_min' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'fotos' => ['nullable', 'array', 'max:6'],
            'fotos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }
}
