<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Validation\Rule;

class ListMedicineDailyStatusesRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/pharmacy/daily-status');
    }

    public function rules(): array
    {
        return [
            'reference_date' => ['nullable', 'date'],
            'availability_status' => ['nullable', Rule::in(['available', 'unavailable'])],
            'medicine_item_id' => ['nullable', 'integer', 'exists:medicine_items,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference_date.date' => 'A data de referência deve estar em formato válido.',
            'availability_status.in' => 'A disponibilidade deve ser "available" ou "unavailable".',
            'medicine_item_id.integer' => 'O identificador do medicamento deve ser um número inteiro.',
            'medicine_item_id.exists' => 'O medicamento informado não foi encontrado.',
            'per_page.integer' => 'A paginação deve ser um número inteiro.',
            'per_page.min' => 'A paginação mínima é 1.',
            'per_page.max' => 'A paginação máxima é 200.',
        ];
    }
}
