<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Trip;
use App\Models\TripClient;

class TripClientRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta solicitação.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Ajuste conforme sua lógica de autorização
    }

    /**
     * Regras de validação para a solicitação.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'trip_id' => 'required|exists:trips,id',
            'client_id' => [
                'required',
                'exists:clients,id',
                // Validação adicional para garantir que client_id não seja duplicado na mesma trip
                Rule::unique('trip_clients')->where(function ($query) {
                    return $query->where('trip_id', $this->trip_id);
                }),
            ],
            'person_type' => 'required|in:passenger,companion',
            'destination_location' => 'nullable|string|max:50',
            'time' => 'required|date_format:H:i',
        ];
    }

    /**
     * Mensagens de erro personalizadas para a validação.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'trip_id.required' => 'O campo viagem é obrigatório.',
            'trip_id.exists' => 'A viagem selecionada não existe.',

            'client_id.required' => 'O campo cliente é obrigatório.',
            'client_id.exists' => 'O cliente selecionado não existe.',
            'client_id.unique' => 'Este cliente já está associado a essa viagem.',

            'person_type.required' => 'O campo tipo de pessoa é obrigatório.',
            'person_type.in' => 'O tipo de pessoa deve ser "passageiro" ou "acompanhante".',

            'destination_location.required' => 'O campo destino é obrigatório.',
            'destination_location.string' => 'O destino deve ser uma string.',
            'destination_location.max' => 'O destino não pode ter mais que 50 caracteres.',

            'time.required' => 'O campo horário é obrigatório.',
            'time.date_format' => 'O campo horário deve estar no formato HH:MM.',
        ];
    }

    /**
     * Regras adicionais de validação após as regras padrão.
     */
    protected function prepareForValidation()
    {
        // Aqui você pode preparar os dados antes da validação se necessário
    }

    /**
     * Validação adicional no método withValidator.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Recupera a viagem (trip) a partir do trip_id
            $trip = Trip::find($this->trip_id);

            if ($trip) {
                // Contagem atual de clientes associados a essa viagem
                $currentClientCount = TripClient::where('trip_id', $this->trip_id)->count();

                // Capacidade do veículo associado à viagem
                $vehicleCapacity = $trip->vehicle->capacity;

                // Verifica se a contagem de clientes excede a capacidade
                if ($currentClientCount >= $vehicleCapacity) {
                    $validator->errors()->add('trip_id', 'A capacidade máxima do veículo foi atingida para esta viagem.');
                }
            }
        });
    }
}


// namespace App\Http\Requests;

// use Illuminate\Foundation\Http\FormRequest;
// use Illuminate\Validation\Rule;

// class TripClientRequest extends FormRequest
// {
//     /**
//      * Determina se o usuário está autorizado a fazer esta solicitação.
//      *
//      * @return bool
//      */
//     public function authorize()
//     {
//         return true; // Ajuste isso conforme a necessidade de autorização do seu sistema
//     }

//     /**
//      * Regras de validação para a solicitação.
//      *
//      * @return array
//      */
//     public function rules()
//     {
//         return [
//             'trip_id' => 'required|exists:trips,id',
//             'client_id' => [
//                 'required',
//                 'exists:clients,id',
//                 // Validação adicional para garantir que client_id não seja duplicado na mesma trip
//                 Rule::unique('trip_clients')->where(function ($query) {
//                     return $query->where('trip_id', $this->trip_id);
//                 }),
//             ],
//             'person_type' => 'required|in:passenger,companion',
//             'destination_location' => 'nullable|string|max:50',
//         ];
//     }

//     /**
//      * Mensagens de erro personalizadas para a validação.
//      *
//      * @return array
//      */
//     public function messages()
//     {
//         return [
//             'trip_id.required' => 'O campo viagem é obrigatório.',
//             'trip_id.exists' => 'A viagem selecionada não existe.',

//             'client_id.required' => 'O campo cliente é obrigatório.',
//             'client_id.exists' => 'O cliente selecionado não existe.',
//             'client_id.unique' => 'Este cliente já está associado a essa viagem.',

//             'person_type.required' => 'O campo tipo de pessoa é obrigatório.',
//             'person_type.in' => 'O tipo de pessoa deve ser "passageiro" ou "acompanhante".',

//             'destination_location.required' => 'O campo destino é obrigatório.',
//             'destination_location.string' => 'O destino deve ser uma string.',
//             'destination_location.max' => 'O destino não pode ter mais que 50 caracteres.',
//         ];
//     }
// }
