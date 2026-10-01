<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'sometimes|string|max:60|unique:page_categories,nome,'.$this->route('id'),
            'icone' => 'nullable|string|max:40',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'nullable|boolean',
        ];
    }
}
