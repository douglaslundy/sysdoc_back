<?php

namespace App\Http\Requests;

use App\Models\AccessProfile;
use App\Models\User;
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
        $user = $this->user();

        // Gestão de usuários (inclusive o campo `profile`, que define permissões) é restrita a
        // administradores e gestores; o escopo do gestor é aplicado no controller e nas regras abaixo.
        return $user !== null && in_array($user->profile, ['admin', 'gestor'], true);
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

        // Cadastro de CPF que já existiu e foi inativado: o registro antigo é reaproveitado
        // (reativado no controller), então não pode barrar a unicidade de CPF/e-mail.
        $userId ??= $this->reactivatableUser()?->id;

        $isGestor = $this->user()?->profile === 'gestor';
        $profileRules = ['required', 'string', 'max:50', Rule::in($validSlugs)];
        if ($isGestor) {
            $profileRules[] = Rule::notIn(['admin']);
        }

        return [
            'profile' => $profileRules,
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
            'secretaria_ids' => [$this->route('user') === null ? 'required' : 'sometimes', 'array', 'min:1'],
            'secretaria_ids.*' => array_values(array_filter([
                'integer', 'distinct', 'exists:secretarias,id',
                // Gestor só vincula a secretarias das quais ele mesmo faz parte.
                $isGestor ? Rule::in($this->user()->secretarias()->pluck('secretarias.id')->all()) : null,
            ])),
            'protocol_unit_ids' => ['nullable', 'array'],
            'protocol_unit_ids.*' => ['integer', 'distinct', 'exists:protocol_organizational_units,id'],
        ];
    }

    /**
     * Usuário inativo (soft delete via `active`) com o mesmo CPF, apenas em criação.
     */
    public function reactivatableUser(): ?User
    {
        if ($this->route('user') !== null || ! $this->filled('cpf')) {
            return null;
        }

        return User::where('cpf', $this->input('cpf'))->where('active', false)->first();
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
            'secretaria_ids.required' => 'Informe ao menos uma secretaria',
            'secretaria_ids.min' => 'Informe ao menos uma secretaria',
            'secretaria_ids.*.in' => 'Você só pode vincular secretarias das quais faz parte',
            'profile.not_in' => 'Gestor não pode atribuir o perfil de administrador',
            'profile.in' => 'Tipo de perfil selecionado não existe ou está inativo',
        ];
    }
}
