<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicketAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Admin em "Todas": a sala define a secretaria; sem sala, informe secretaria_id.
            'secretaria_id' => app(\App\Services\Secretarias\ActiveSecretaria::class)->isAll()
                ? ['required_without:roomId', 'nullable', 'integer', \Illuminate\Validation\Rule::exists('secretarias', 'id')->where('ativa', true)]
                : ['nullable'],
            'clientId' => 'required|integer|exists:clients,id',
            'prefix' => 'nullable|string|max:3',
            'roomId' => ['nullable', 'integer', \App\Support\EscopoAlmoxarifado::existeNaAtiva('attendance_rooms')],
        ];
    }
}
