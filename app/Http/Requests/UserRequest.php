<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "profile" => ['required', 'string', 'max:50', Rule::in('admin', 'partner', 'user', 'driver')],
            'name' => 'required|string|max:50',
            'email' => 'string|required|max:100|unique:users,email,' . request()->id,
            'cpf' => 'string|required|max:18|unique:users,cpf,' . request()->id,
            'email_verified_at' => ['nullable', 'date'],
            'active' => ['Boolean'],
            'inactive_date' => ['nullable', 'date'],
        ];
    }

    public function messages()
    {
        return [
            'profile.required' => 'O tipo de perfil do usuário é obrigatorio',
            'name.required' => 'O Nome do usuário é obrigatorio',
            'name.max' => 'O Nome não deve possuir acima de 50 caracteres',
            'cpf.unique' => 'Ja existe um usuário cadastrado com este CPF',
            'cpf.required' => 'O campo CPF é obrigatório',
            'email.unique' => 'Ja existe um usuário cadastrado com este E-Mail',
            'email.required' => 'O campo E-mail é obrigatório',
            'profile.in' => 'Tipo de Perfil selecionado não existe',
        ];
    }
}
