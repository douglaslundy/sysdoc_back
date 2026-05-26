<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;

class ListMedicineItemsRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/pharmacy/medicines');
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'active' => ['nullable', 'boolean'],
            'is_free_distribution' => ['nullable', 'boolean'],
            'is_controlled' => ['nullable', 'boolean'],
            'is_judicial_order' => ['nullable', 'boolean'],
            'is_high_cost' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'O termo de busca deve ser um texto.',
            'search.max' => 'O termo de busca deve ter no máximo 120 caracteres.',
            'active.boolean' => 'O filtro de ativo deve ser verdadeiro ou falso.',
            'is_free_distribution.boolean' => 'O filtro de distribuição gratuita deve ser verdadeiro ou falso.',
            'is_controlled.boolean' => 'O filtro de medicamento controlado deve ser verdadeiro ou falso.',
            'is_judicial_order.boolean' => 'O filtro de ordem judicial deve ser verdadeiro ou falso.',
            'is_high_cost.boolean' => 'O filtro de alto custo deve ser verdadeiro ou falso.',
            'per_page.integer' => 'A paginação deve ser um número inteiro.',
            'per_page.min' => 'A paginação mínima é 1.',
            'per_page.max' => 'A paginação máxima é 200.',
        ];
    }
}
