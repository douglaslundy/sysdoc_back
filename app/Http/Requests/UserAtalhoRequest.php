<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserAtalhoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:80',
            // Só páginas internas do sistema; o controller confere se o usuário pode abri-las.
            'url' => ['required', 'string', 'max:500', 'regex:#^/(?!/)#'],
            'icone' => 'nullable|string|max:40',
        ];
    }
}
