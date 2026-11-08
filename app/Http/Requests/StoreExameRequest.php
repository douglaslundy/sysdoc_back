<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use App\Support\EscopoAlmoxarifado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/laboratorio/exames');
    }

    public function rules(): array
    {
        // PUT: a secretaria é a do exame (de outra secretaria = 404); POST: a ativa, ou a informada pelo admin em "Todas".
        $id = $this->route('exame');
        $sid = EscopoAlmoxarifado::alvo(\App\Models\Exame::class, $id, $this->input('secretaria_id'));

        $rules = [
            ...($id === null ? ['secretaria_id' => EscopoAlmoxarifado::regraCampo()] : []),
            'nome' => 'required|string|max:100',
            'codigo' => 'required|string|max:30',
            'categoria_exame_id' => ['nullable', EscopoAlmoxarifado::existe('categoria_exames', $sid)],
            'descricao' => 'nullable|string',
            'ativo' => 'boolean',
        ];

        if ($this->method() === 'PUT') {
            $rules['codigo'] = ['required', 'string', 'max:30', EscopoAlmoxarifado::unico('exames', 'codigo', $sid)->ignore($id)];
        } else {
            $rules['codigo'] = ['required', 'string', 'max:30', EscopoAlmoxarifado::unico('exames', 'codigo', $sid)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => 'O campo nome não pode ter mais de :max caracteres.',
            'codigo.required' => 'O campo código é obrigatório.',
            'codigo.unique' => 'Este código já está em uso.',
            'codigo.max' => 'O campo código não pode ter mais de :max caracteres.',
        ];
    }
}
