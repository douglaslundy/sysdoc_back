<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Validation\Rule;

class UpsertMedicineDailyStatusRequest extends BaseApiFormRequest
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
            'medicine_item_id' => ['required', 'integer', 'exists:medicine_items,id'],
            'reference_date' => ['required', 'date'],
            'availability_status' => ['required', Rule::in(['available', 'unavailable'])],
            'available_quantity' => ['nullable', 'numeric', 'min:0'],
            'restock_forecast_date' => ['nullable', 'date'],
            'public_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'medicine_item_id.required' => 'O medicamento é obrigatório.',
            'medicine_item_id.integer' => 'O identificador do medicamento deve ser um número inteiro.',
            'medicine_item_id.exists' => 'O medicamento informado não foi encontrado.',
            'reference_date.required' => 'A data de referência é obrigatória.',
            'reference_date.date' => 'A data de referência deve estar em formato válido.',
            'availability_status.required' => 'A disponibilidade é obrigatória.',
            'availability_status.in' => 'A disponibilidade deve ser "disponível" ou "indisponível" (valores técnicos: available/unavailable).',
            'available_quantity.numeric' => 'A quantidade disponível deve ser numérica.',
            'available_quantity.min' => 'A quantidade disponível não pode ser negativa.',
            'restock_forecast_date.date' => 'A previsão de reposição deve estar em formato de data válido.',
            'public_note.string' => 'A observação pública deve ser um texto.',
            'public_note.max' => 'A observação pública deve ter no máximo 1000 caracteres.',
        ];
    }
}
