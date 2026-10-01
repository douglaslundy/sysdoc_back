<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReordenarExameCampoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ordem' => 'required|array',
            'ordem.*' => 'integer|exists:exame_campos,id',
        ];
    }
}
