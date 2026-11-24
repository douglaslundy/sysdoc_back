<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\LimpaTextoRico;
use App\Rules\TextoRico;
use Illuminate\Foundation\Http\FormRequest;

class OuvidoriaComentarioRequest extends FormRequest
{
    use LimpaTextoRico;

    protected function prepareForValidation(): void
    {
        $this->limparTextoRico(['observacao']);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['observacao' => ['required', 'string', new TextoRico(2000, 2)]];
    }
}
