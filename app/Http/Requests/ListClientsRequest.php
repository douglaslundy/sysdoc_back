<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;

class ListClientsRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccessAny($user, [
                '/clients',
                '/queue',
                '/trips',
                '/laboratorio/pedidos',
                '/attendance/tickets',
            ]);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'O termo de busca deve ser um texto.',
            'search.max' => 'O termo de busca deve ter no maximo 120 caracteres.',
            'per_page.integer' => 'A paginacao deve ser um numero inteiro.',
            'per_page.min' => 'A paginacao minima e 1.',
            'per_page.max' => 'A paginacao maxima e 100.',
            'limit.integer' => 'O limite deve ser um numero inteiro.',
            'limit.min' => 'O limite minimo e 1.',
            'limit.max' => 'O limite maximo e 50.',
        ];
    }
}
