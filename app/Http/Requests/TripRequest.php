<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Defina como true se a autorização já estiver sendo tratada em outro lugar
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'route_id' => 'required|exists:routes,id',
            'departure_time' => 'nullable',
            'departure_date' => 'required|date',
            'obs' => 'nullable|string|max:300',
            'driver_id' => 'nullable|exists:users,id',
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
