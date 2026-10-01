<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EligibleProtocolUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_id' => 'nullable|integer|exists:protocol_organizational_units,id',
        ];
    }
}
