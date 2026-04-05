<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    // Determina se o usuário está autorizado a fazer essa request
    public function authorize()
    {
        return true; // Alterar para 'false' se for necessário controlar o acesso
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'origin_state' => strtoupper((string) $this->origin_state),
            'destination_state' => strtoupper((string) $this->destination_state),
        ]);
    }

    // Regras de validação
    public function rules()
    {
        return [
            'origin' => 'required|string|max:50',
            'destination' => 'required|string|max:50',

            'origin_state' => 'required|string|size:2|exists:states,code',
            'destination_state' => 'required|string|size:2|exists:states,code',

            'distance' => 'required|numeric',
        ];
    }

    // Mensagens de erro personalizadas
    public function messages()
    {
        return [
            'origin.required' => 'O campo origem é obrigatório.',
            'origin.max' => 'A origem não pode ter mais que 50 caracteres.',

            'destination.required' => 'O campo destino é obrigatório.',
            'destination.max' => 'O destino não pode ter mais que 50 caracteres.',

            'origin_state.required' => 'O estado de origem é obrigatório.',
            'origin_state.size' => 'O estado de origem deve ter exatamente 2 caracteres.',
            'origin_state.exists' => 'O estado de origem é inválido.',

            'destination_state.required' => 'O estado de destino é obrigatório.',
            'destination_state.size' => 'O estado de destino deve ter exatamente 2 caracteres.',
            'destination_state.exists' => 'O estado de destino é inválido.',

            'distance.required' => 'O campo distância é obrigatório.',
            'distance.numeric' => 'A distância deve ser um valor numérico.',
        ];
    }
}
