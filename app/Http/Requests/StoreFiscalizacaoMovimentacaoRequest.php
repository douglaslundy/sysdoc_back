<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;

class StoreFiscalizacaoMovimentacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PagePermissionService::class)->canAccess($user, '/fiscalizacoes');
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:2000'],
            'publico' => ['sometimes', 'boolean'],
        ];
    }
}
