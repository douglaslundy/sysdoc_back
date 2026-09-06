<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFiscalizacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PagePermissionService::class)->canAccess($user, '/fiscalizacoes');
    }

    public function rules(): array
    {
        return [
            'estabelecimento_id' => ['required', 'integer', 'exists:estabelecimentos,id'],
            'data_visita' => ['required', 'date'],
            'resultado' => ['required', Rule::in(['Conforme', 'Não conforme', 'Notificação', 'Auto de infração'])],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // fiscal_id nunca vem do cliente — é sempre o usuário autenticado.
        $this->request->remove('fiscal_id');
    }
}
