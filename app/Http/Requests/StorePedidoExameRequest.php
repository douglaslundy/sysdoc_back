<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use App\Support\EscopoAlmoxarifado;
use Illuminate\Foundation\Http\FormRequest;

class StorePedidoExameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/laboratorio/pedidos');
    }

    public function rules(): array
    {
        $sid = EscopoAlmoxarifado::alvo(\App\Models\PedidoExame::class, null, $this->input('secretaria_id'));

        return [
            'secretaria_id' => EscopoAlmoxarifado::regraCampo(),
            'client_id' => 'required|exists:clients,id',
            'medico_solicitante_id' => ['nullable', EscopoAlmoxarifado::existe('medicos_solicitantes', $sid)],
            'data_pedido' => 'required|date',
            'data_coleta' => 'nullable|date',
            'observacoes' => 'nullable|string',
            'exames' => 'required|array|min:1',
            'exames.*' => ['required', 'integer', 'distinct', EscopoAlmoxarifado::existe('exames', $sid)],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'O campo cliente é obrigatório.',
            'client_id.exists' => 'Cliente não encontrado.',
            'data_pedido.required' => 'A data do pedido é obrigatória.',
            'exames.required' => 'Selecione ao menos um exame.',
            'exames.min' => 'Selecione ao menos um exame.',
            'exames.*.exists' => 'Um dos exames selecionados não existe.',
            'exames.*.distinct' => 'O mesmo exame não pode ser selecionado mais de uma vez.',
        ];
    }
}
