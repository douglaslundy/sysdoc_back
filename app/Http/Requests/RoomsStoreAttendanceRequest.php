<?php

namespace App\Http\Requests;

use App\Services\Secretarias\ActiveSecretaria;
use App\Support\EscopoAlmoxarifado;
use Illuminate\Foundation\Http\FormRequest;

class RoomsStoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'secretaria_id' => EscopoAlmoxarifado::regraCampo(),
            'name' => ['required', 'string', 'max:100', EscopoAlmoxarifado::unico('attendance_rooms', 'name', app(ActiveSecretaria::class)->forCreate($this->input('secretaria_id')) ?? 0)],
            'description' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ];
    }
}
