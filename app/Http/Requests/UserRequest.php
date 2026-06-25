<?php

namespace App\Http\Requests;

use App\Models\AccessProfile;
use App\Rules\ValidCpf;
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
        $validSlugs = AccessProfile::where('ativo', true)->pluck('slug')->toArray();
        $routeUser = $this->route('user');
        $userId = is_object($routeUser) ? $routeUser->id : $routeUser;

        return [
            'profile' => ['required', 'string', 'max:50', Rule::in($validSlugs)],
            'name' => 'required|string|max:50',
            'email' => ['string', 'required', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'cpf' => ['string', 'required', 'max:18', Rule::unique('users', 'cpf')->ignore($userId), new ValidCpf()],
            'email_verified_at' => ['nullable', 'date'],
            'active' => ['Boolean'],
            'inactive_date' => ['nullable', 'date'],
            'is_rt_psf'    => ['nullable', 'boolean'],
            'rt_all_teams' => ['nullable', 'boolean'],
            'chat_access_override' => ['nullable', 'boolean'],
            'protocol_unit_ids' => ['nullable', 'array'],
            'protocol_unit_ids.*' => ['integer', 'distinct', 'exists:protocol_organizational_units,id'],
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
            'profile.in' => 'Tipo de Perfil selecionado não existe ou está inativo',
        ];
    }
}
