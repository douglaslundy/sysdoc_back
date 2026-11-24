<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadOuvidoriaLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Sem SVG: pode carregar script e é servido do mesmo domínio da API.
        return ['logo' => 'required|file|mimes:png,jpg,jpeg,webp|max:1024'];
    }
}
