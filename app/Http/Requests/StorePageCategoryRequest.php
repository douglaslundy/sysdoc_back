<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePageCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:60|unique:page_categories,nome',
            'icone' => 'nullable|string|max:40',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'nullable|boolean',
        ];
    }
}
