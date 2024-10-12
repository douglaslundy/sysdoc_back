<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    // Determina se o usuário está autorizado a fazer essa request
    public function authorize()
    {
        return true; // Alterar para 'false' se não quiser que qualquer usuário possa fazer essa request.
    }

    // Regras de validação
    public function rules()
    {
        return [
            'brand' => 'required|string|max:20',
            'model' => 'required|string|max:20',
            'color' => 'required|string|max:10',
            'license_plate' => 'required|string|size:7',
            'renavan' => 'required|string|size:11',
            'chassis' => 'required|string|size:17',
            'capacity' => 'required|integer|max:999|min:1',
            'year' => 'required|numeric|digits:4',
        ];
    }

    // Mensagens de erro personalizadas
    public function messages()
    {
        return [
            'brand.required' => 'O campo marca é obrigatório.',
            'brand.max' => 'A marca não pode ter mais que 20 caracteres.',
            'model.required' => 'O campo modelo é obrigatório.',
            'model.max' => 'O modelo não pode ter mais que 20 caracteres.',
            'color.required' => 'O campo cor é obrigatório.',
            'color.max' => 'A cor não pode ter mais que 10 caracteres.',
            'license_plate.required' => 'O campo placa é obrigatório.',
            'license_plate.size' => 'A placa deve ter exatamente 7 caracteres.',
            'renavan.required' => 'O campo Renavam é obrigatório.',
            'renavan.size' => 'O Renavam deve ter exatamente 11 caracteres.',
            'chassis.required' => 'O campo chassi é obrigatório.',
            'chassis.size' => 'O chassi deve ter exatamente 17 caracteres.',
            'capacity.required' => 'O campo capacidade é obrigatório.',
            'capacity.integer' => 'A capacidade deve ser um número inteiro.',
            'capacity.max' => 'A capacidade máxima permitida é de 999.',
            'capacity.min' => 'A capacidade mínima permitida é de 1.',
            'year.required' => 'O campo ano é obrigatório.',
            'year.numeric' => 'O ano deve ser numérico.',
            'year.digits' => 'O ano deve ter exatamente 4 dígitos.',
        ];
    }
}
