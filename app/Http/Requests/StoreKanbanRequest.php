<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKanbanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:200',
            'descricao' => 'nullable|string',
            'status' => 'nullable|string|max:40',
            'prioridade' => 'nullable|string|max:20',
            'vencimento' => 'nullable|date',
            'responsavel_id' => 'nullable|integer|exists:users,id',
            'visibility' => 'nullable|string|in:public,private',
            'ordem' => 'nullable|integer|min:0',
        ];
    }
}
