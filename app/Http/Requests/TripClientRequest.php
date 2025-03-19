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
    // public function rules()
    // {
    //     return [
    //         'id' => 'nullable|int',
    //         'trip_id' => 'required|exists:trips,id',
    //         'client_id' => [
    //             'required',
    //             'exists:clients,id',
    //             // Validação adicional para garantir que client_id não seja duplicado na mesma trip
    //             Rule::unique('trip_clients')->where(function ($query) {
    //                 return $query->where('trip_id', $this->trip_id);
    //             }),
    //         ],
    //         'person_type' => 'required|in:passenger,companion',
    //         'ohone' => 'nullable|string|max:20',
    //         'departure_location' => 'nullable|string|max:50',
    //         'destination_location' => 'nullable|string|max:50',
    //         'time' => 'required|date_format:H:i',
    //     ];
    // }

public function rules()
{
    return [
        'id' => 'nullable|int',
        'trip_id' => 'required|exists:trips,id',
        'client_id' => [
            'required',
            'exists:clients,id',
            function ($attribute, $value, $fail) {
                if ($this->id) {
                    return; // Se o ID for válido, ignora a regra de unicidade
                }
                
                $exists = \DB::table('trip_clients')
                    ->where('trip_id', $this->trip_id)
                    ->where('client_id', $value)
                    ->exists();
                
                if ($exists) {
                    $fail('O cliente já está cadastrado nesta viagem.');
                }
            },
        ],
        'person_type' => 'required|in:passenger,companion',
        'phone' => 'nullable|string|max:20', // Corrigido "ohone" para "phone"
        'departure_location' => 'nullable|string|max:50',
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

            'phone.required' => 'O campo TELEFONE é obrigatório.',
            'phone.string' => 'O campo TELEFONE deve ser uma string.',
            'phone.max' => 'O campo TELEFONE não pode ter mais que 20 caracteres.',

            'departure_location.required' => 'O campo saída é obrigatório.',
            'departure_location.string' => 'A saída deve ser uma string.',
            'departure_location.max' => 'A saída não pode ter mais que 50 caracteres.',

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
                // Verifica se a viagem possui um veículo associado
                if ($trip->vehicle) {
                    // Contagem atual de clientes associados a essa viagem
                    $currentClientCount = TripClient::where('trip_id', $this->trip_id)->count();

                    // Capacidade do veículo associado à viagem
                    $vehicleCapacity = $trip->vehicle->capacity;

                    // Verifica se a contagem de clientes excede a capacidade
                    if ($currentClientCount >= $vehicleCapacity - 1) {
                        $validator->errors()->add('trip_id', 'A capacidade máxima do veículo foi atingida para esta viagem.');
                    }
                }
                // Caso não haja veículo associado, a validação não fará nada e passará.
            }
        });
    }
}
