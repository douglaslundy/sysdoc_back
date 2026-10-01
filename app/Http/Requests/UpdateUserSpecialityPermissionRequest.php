<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSpecialityPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            'permissions.*.speciality_id' => ['required', 'integer', 'distinct', 'exists:specialities,id'],
            'permissions.*.can_view' => ['boolean'],
            'permissions.*.can_edit' => ['boolean'],
            'permissions.*.can_insert' => ['boolean'],
        ];
    }
}
