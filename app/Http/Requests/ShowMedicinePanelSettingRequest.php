<?php

namespace App\Http\Requests;

use App\Support\EscopoAlmoxarifado;
use Illuminate\Foundation\Http\FormRequest;

class ShowMedicinePanelSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Admin em "Todas" escolhe a secretaria (?secretaria_id=), validada; os demais usam a ativa.
        return ['secretaria_id' => EscopoAlmoxarifado::regraCampo()];
    }
}
