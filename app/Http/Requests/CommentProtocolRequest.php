<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\LimpaTextoRico;
use App\Rules\TextoRico;
use Illuminate\Foundation\Http\FormRequest;

class CommentProtocolRequest extends FormRequest
{
    use LimpaTextoRico;

    protected function prepareForValidation(): void
    {
        $this->limparTextoRico(['conteudo']);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conteudo' => ['required', 'string', new TextoRico(16000, 1)],
            'privado' => 'nullable|boolean',
            'tipo' => 'nullable|string|max:30',
        ];
    }
}
