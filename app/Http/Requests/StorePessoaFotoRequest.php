<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePessoaFotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Envie uma imagem.',
            'photo.image' => 'O arquivo precisa ser uma imagem (JPG, PNG ou WebP).',
            'photo.mimes' => 'O arquivo precisa ser uma imagem (JPG, PNG ou WebP).',
            'photo.max' => 'A foto pode ter no máximo 4 MB.',
        ];
    }
}
