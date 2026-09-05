<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoriaExameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/laboratorio/categorias');
    }

    public function rules(): array
    {
        $id = $this->route('categoria');

        return [
            'nome' => [
                'required',
                'string',
                'max:80',
                Rule::unique('categoria_exames', 'nome')->ignore($id),
            ],
            'ativo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.unique' => 'Esta categoria já está cadastrada.',
            'nome.max' => 'O nome não pode ter mais de :max caracteres.',
        ];
    }
}
