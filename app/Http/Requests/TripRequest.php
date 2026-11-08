<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;

class TripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/trips');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // Edição: a secretaria é a da viagem (404 se for de outra); criação: a ativa (ou a informada pelo admin em "Todas").
        $trip = $this->route('trip');
        $sid = $trip instanceof \App\Models\Trip
            ? (int) $trip->secretaria_id
            : (app(\App\Services\Secretarias\ActiveSecretaria::class)->forCreate($this->input('secretaria_id')) ?? 0);

        return [
            'secretaria_id' => $trip instanceof \App\Models\Trip ? ['nullable'] : \App\Support\EscopoAlmoxarifado::regraCampo(),
            'user_id' => ['nullable', \App\Services\Secretarias\SecretariaMembership::regraUsuario($sid)],
            'vehicle_id' => ['nullable', \App\Support\EscopoAlmoxarifado::existe('vehicles', $sid)],
            'route_id' => ['required', \App\Support\EscopoAlmoxarifado::existe('routes', $sid)],
            'departure_time' => 'nullable',
            'departure_date' => 'required|date',
            'obs' => 'nullable|string|max:300',
            'driver_id' => ['nullable', \App\Services\Secretarias\SecretariaMembership::regraUsuario($sid)],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'user_id.exists' => 'O usuário selecionado não existe.',
            'vehicle_id.exists' => 'O veículo selecionado não existe.',
            'route_id.required' => 'A rota é obrigatória.',
            'route_id.exists' => 'A rota selecionada não existe.',
            'departure_time.date_format' => 'O horário de partida deve estar no formato HH:MM.',
            'departure_date.required' => 'A data de partida é obrigatória.',
            'departure_date.date' => 'A data de partida deve ser uma data válida.',
            'obs.max' => 'A observação não pode ter mais de 300 caracteres.',
            'driver_id.exists' => 'O motorista selecionado não existe.',
        ];
    }
}
