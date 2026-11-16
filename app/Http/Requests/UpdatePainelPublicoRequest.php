<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePainelPublicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Só caminho interno ("/pagina") ou http(s): bloqueia javascript:, data: e "//host".
        $url = ['required', 'string', 'max:500', 'regex:#^(/(?!/)|https?://)#i'];

        return [
            'pagina_url' => $url,
            'esquerda_url' => $url,
            'direita_url' => $url,
            'largura_esquerda' => 'required|integer|min:20|max:80',
        ];
    }
}
