<?php

namespace App\Http\Requests;

class DeleteDuplicateClientsRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->profile === 'admin';
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Informe ao menos um client para exclusao.',
            'ids.array' => 'Os clients informados sao invalidos.',
            'ids.min' => 'Informe ao menos um client para exclusao.',
            'ids.*.integer' => 'Os IDs informados precisam ser numericos.',
            'ids.*.distinct' => 'Nao envie IDs duplicados para exclusao.',
            'ids.*.min' => 'Os IDs informados sao invalidos.',
        ];
    }
}