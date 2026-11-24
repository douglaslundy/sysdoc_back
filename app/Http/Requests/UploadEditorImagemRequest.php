<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Imagem inserida no editor de texto rico: sem SVG (pode carregar script). */
class UploadEditorImagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['imagem' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048']];
    }

    public function messages(): array
    {
        return [
            'imagem.mimes' => 'Use PNG, JPG ou WEBP.',
            'imagem.max' => 'A imagem deve ter até 2 MB.',
        ];
    }
}
