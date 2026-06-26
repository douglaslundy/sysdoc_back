<?php

namespace App\Http\Requests;

use App\Models\AccessProfile;
use App\Rules\PhoneValidation;
use App\Rules\ValidCpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $preferredName = $this->input('preferred_name');
        $phone = $this->input('phone');

        if ($preferredName === null) {
            $normalizedPreferredName = null;
        } else {
            $normalizedPreferredName = mb_strtoupper(trim((string) $preferredName), 'UTF-8');
        }

        $normalizedPhone = $phone === null
            ? null
            : preg_replace('/\D+/', '', trim((string) $phone));

        $this->merge([
            'preferred_name' => $normalizedPreferredName === null || $normalizedPreferredName === '' ? null : $normalizedPreferredName,
            'phone' => $normalizedPhone === null || $normalizedPhone === '' ? null : $normalizedPhone,
        ]);
    }

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
            'preferred_name' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', new PhoneValidation],
            'email' => ['string', 'required', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'cpf' => ['string', 'required', 'max:18', Rule::unique('users', 'cpf')->ignore($userId), new ValidCpf()],
            'email_verified_at' => ['nullable', 'date'],
            'active' => ['Boolean'],
            'inactive_date' => ['nullable', 'date'],
            'is_rt_psf' => ['nullable', 'boolean'],
            'rt_all_teams' => ['nullable', 'boolean'],
            'chat_access_override' => ['nullable', 'boolean'],
            'protocol_unit_ids' => ['nullable', 'array'],
            'protocol_unit_ids.*' => ['integer', 'distinct', 'exists:protocol_organizational_units,id'],
        ];
    }

    public function messages()
    {
        return [
            'profile.required' => 'O tipo de perfil do usuário é obrigatório',
            'name.required' => 'O nome do usuário é obrigatório',
            'name.max' => 'O nome não deve possuir acima de 50 caracteres',
            'preferred_name.max' => 'O campo Como gostaria de ser chamado não deve possuir acima de 50 caracteres',
            'cpf.unique' => 'Já existe um usuário cadastrado com este CPF',
            'cpf.required' => 'O campo CPF é obrigatório',
            'email.unique' => 'Já existe um usuário cadastrado com este e-mail',
            'email.required' => 'O campo E-mail é obrigatório',
            'profile.in' => 'Tipo de perfil selecionado não existe ou está inativo',
        ];
    }
}
