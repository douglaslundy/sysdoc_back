<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use App\Support\EscopoAlmoxarifado;
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
        $sid = EscopoAlmoxarifado::alvo(\App\Models\CategoriaExame::class, $id, $this->input('secretaria_id'));

        return [
            ...($id === null ? ['secretaria_id' => EscopoAlmoxarifado::regraCampo()] : []),
            'nome' => [
                'required',
                'string',
                'max:80',
                EscopoAlmoxarifado::unico('categoria_exames', 'nome', $sid)->ignore($id),
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
