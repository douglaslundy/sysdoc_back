<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardAtalhoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:60',
            'url' => ['required', 'string', 'max:500', 'regex:#^(/(?!/)|https?://)#i'],
            'icone' => 'nullable|string|max:40',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'nullable|boolean',
        ];
    }
}
