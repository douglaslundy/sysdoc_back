<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules()
    {
        return [
            'nome' => 'required|string|max:100',
            'mother' => 'required|string|max:100',
            'cpf' => 'required|string|unique:clients,cpf|max:18',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'obs' => 'nullable|string|max:200',
            'born_date' => 'nullable|date',
            'sexo' => 'required|in:MASCULINE,FEMININE,INDETERMINATE',
            'active' => 'boolean'
        ];
    }

    public function messages()
    {
        return [
            'nome.required' => 'O campo nome é obrigatório',
            'nome.max' => 'O campo nome não pode ter mais de :max caracteres',
            'mother.required' => 'O campo mãe é obrigatório',
            'mother.max' => 'O campo mãe não pode ter mais de :max caracteres',
            'cpf.required' => 'O campo CPF é obrigatório',
            'cpf.regex' => 'O CPF deve estar no formato 000.000.000-00',
            'cpf.unique' => 'Já existe um cliente cadastrado com este CPF',
            'phone.max' => 'O campo telefone não pode ter mais de :max caracteres',
            'email.email' => 'O campo email deve conter um endereço de email válido',
            'email.max' => 'O campo email não pode ter mais de :max caracteres',
            'obs.max' => 'O campo observações não pode ter mais de :max caracteres',
            'born_date.date' => 'O campo data de nascimento deve ser uma data válida',
            'sexo.required' => 'O campo sexo é obrigatório',
            'sexo.in' => 'O valor do campo sexo é inválido',
            'active.boolean' => 'O campo ativo deve ser um valor booleano',
        ];
    }
}
