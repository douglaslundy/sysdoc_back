<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSystemPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:80',
            'path' => 'required|string|max:120|unique:system_pages,path',
            'icone' => 'nullable|string|max:40',
            'categoria' => 'nullable|string|max:60',
            'category_id' => 'nullable|integer|exists:page_categories,id',
            'ordem' => 'nullable|integer|min:1',
        ];
    }
}
