<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;

class ListMedicineMonthlyAcquisitionsRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/pharmacy/monthly-acquisitions');
    }

    public function rules(): array
    {
        return [
            'reference_month' => ['nullable', 'regex:/^\d{4}\-(0[1-9]|1[0-2])$/'],
            'medicine_item_id' => ['nullable', 'integer', 'exists:medicine_items,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference_month.regex' => 'O mês de referência deve estar no formato AAAA-MM.',
            'medicine_item_id.integer' => 'O identificador do medicamento deve ser um número inteiro.',
            'medicine_item_id.exists' => 'O medicamento informado não foi encontrado.',
            'per_page.integer' => 'A paginação deve ser um número inteiro.',
            'per_page.min' => 'A paginação mínima é 1.',
            'per_page.max' => 'A paginação máxima é 200.',
        ];
    }
}
