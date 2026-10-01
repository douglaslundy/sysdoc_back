<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpecialityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_user' => 'required|exists:users,id',
            'name' => 'required|string|max:50',
            'allows_session_scheduling' => 'sometimes|boolean',
        ];
    }
}
