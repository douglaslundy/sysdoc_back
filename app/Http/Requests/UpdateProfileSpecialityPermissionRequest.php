<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileSpecialityPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // acesso restrito pelo middleware 'admin' na rota
    }

    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            'permissions.*.speciality_id' => ['required', 'integer', 'distinct', \App\Support\EscopoAlmoxarifado::existeNaAtiva('specialities')],
            'permissions.*.can_view' => ['boolean'],
            'permissions.*.can_edit' => ['boolean'],
            'permissions.*.can_insert' => ['boolean'],
        ];
    }
}
