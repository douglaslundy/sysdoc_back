<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForwardProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destino_unit_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'destino_user_id' => 'nullable|integer|exists:users,id',
            'observacao' => 'nullable|string',
        ];
    }
}
