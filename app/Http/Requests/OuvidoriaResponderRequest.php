<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\LimpaTextoRico;
use App\Rules\TextoRico;
use Illuminate\Foundation\Http\FormRequest;

class OuvidoriaResponderRequest extends FormRequest
{
    use LimpaTextoRico;

    protected function prepareForValidation(): void
    {
        $this->limparTextoRico(['resposta']);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['resposta' => ['required', 'string', new TextoRico(5000, 5)]];
    }
}
