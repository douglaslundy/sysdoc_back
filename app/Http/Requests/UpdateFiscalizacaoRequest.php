<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use App\Support\EscopoAlmoxarifado;
use Illuminate\Validation\Rule;

class UpdateFiscalizacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PagePermissionService::class)->canAccess($user, '/fiscalizacoes');
    }

    public function rules(): array
    {
        // 404 se a fiscalizacao for de outra secretaria; o estabelecimento tem de ser da mesma secretaria.
        $sid = EscopoAlmoxarifado::alvo(\App\Models\Fiscalizacao::class, $this->route('fiscalizacao'));

        return [
            'estabelecimento_id' => ['sometimes', 'required', 'integer', EscopoAlmoxarifado::existe('estabelecimentos', $sid)],
            'data_visita' => ['sometimes', 'required', 'date'],
            'resultado' => ['sometimes', 'required', Rule::in(['Conforme', 'Não conforme', 'Notificação', 'Auto de infração', 'Pendente de apuração'])],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'visivel_ao_denunciante' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('fiscal_id');
    }
}
