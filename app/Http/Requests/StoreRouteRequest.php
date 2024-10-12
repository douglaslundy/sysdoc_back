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

    // Regras de validação
    public function rules()
    {
        return [
            'origin' => 'required|string|max:30',
            'destination' => 'required|string|max:30',
            'distance' => 'required|numeric',
        ];
    }

    // Mensagens de erro personalizadas
    public function messages()
    {
        return [
            'origin.required' => 'O campo origem é obrigatório.',
            'origin.max' => 'A origem não pode ter mais que 30 caracteres.',
            'destination.required' => 'O campo destino é obrigatório.',
            'destination.max' => 'O destino não pode ter mais que 30 caracteres.',
            'distance.required' => 'O campo distância é obrigatório.',
            'distance.numeric' => 'A distância deve ser um valor numérico.',
        ];
    }
}
