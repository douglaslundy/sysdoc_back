<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conteudo' => 'required|string',
            'privado' => 'nullable|boolean',
            'tipo' => 'nullable|string|max:30',
        ];
    }
}
