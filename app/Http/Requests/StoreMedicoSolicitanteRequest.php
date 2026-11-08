<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use App\Support\EscopoAlmoxarifado;
use Illuminate\Validation\Rule;

class StoreMedicoSolicitanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/laboratorio/medicos');
    }

    public function rules(): array
    {
        $ignorId = $this->route('medico');
        $id = $ignorId instanceof \Illuminate\Database\Eloquent\Model ? $ignorId->getKey() : $ignorId;
        $sid = EscopoAlmoxarifado::alvo(\App\Models\MedicoSolicitante::class, $id, $this->input('secretaria_id'));

        return [
            ...($id === null ? ['secretaria_id' => EscopoAlmoxarifado::regraCampo()] : []),
            'nome' => 'required|string|max:100',
            'crm' => [
                'nullable', 'string', 'max:20',
                EscopoAlmoxarifado::unico('medicos_solicitantes', 'crm', $sid)->ignore($id),
            ],
            'uf_crm' => 'nullable|string|size:2',
            'especialidade' => 'nullable|string|max:80',
            'telefone' => 'nullable|string|max:20',
            'ativo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do médico é obrigatório.',
            'crm.unique' => 'Este CRM já está cadastrado.',
        ];
    }
}
