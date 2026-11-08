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
        $sid = (int) (\App\Models\Exame::find($this->route('exame'))?->secretaria_id ?? abort(404, 'Exame não encontrado.'));

        return [
            'ordem' => 'required|array',
            'ordem.*' => ['integer', \App\Support\EscopoAlmoxarifado::existeCampoDeExame($sid)],
        ];
    }
}
