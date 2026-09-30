<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;
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
        return [
            'estabelecimento_id' => ['sometimes', 'required', 'integer', 'exists:estabelecimentos,id'],
            'data_visita' => ['sometimes', 'required', 'date'],
            'resultado' => ['sometimes', 'required', Rule::in(['Conforme', 'Não conforme', 'Notificação', 'Auto de infração', 'Pendente de apuração'])],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'visivel_ao_denunciante' => ['sometimes', 'boolean'],
            'mensagem_publica' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('fiscal_id');
    }
}
