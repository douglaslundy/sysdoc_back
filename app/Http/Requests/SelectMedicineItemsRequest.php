<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;

class SelectMedicineItemsRequest extends BaseApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $active = $this->query('active');

        if (is_string($active)) {
            $normalized = strtolower(trim($active));
            if ($normalized === 'true') {
                $this->merge(['active' => 1]);
            } elseif ($normalized === 'false') {
                $this->merge(['active' => 0]);
            }
        }
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccessAny($user, [
                '/pharmacy/medicines',
                '/pharmacy/daily-status',
                '/pharmacy/monthly-acquisitions',
            ]);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'active' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'O termo de busca deve ser um texto.',
            'search.max' => 'O termo de busca deve ter no mÃ¡ximo 120 caracteres.',
            'active.boolean' => 'O filtro de ativo deve ser verdadeiro ou falso.',
            'limit.integer' => 'O limite deve ser um nÃºmero inteiro.',
            'limit.min' => 'O limite mÃ­nimo Ã© 1.',
            'limit.max' => 'O limite mÃ¡ximo Ã© 500.',
        ];
    }
}
