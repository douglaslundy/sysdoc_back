<?php

namespace App\Http\Requests;

use App\Support\EscopoAlmoxarifado;
use Illuminate\Foundation\Http\FormRequest;

class InsertSectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Só a secretaria: o 'name' segue validado no controller (resposta 200 com 'error', formato legado).
    public function rules(): array
    {
        return ['secretaria_id' => EscopoAlmoxarifado::regraCampo()];
    }
}
