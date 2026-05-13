<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Validation\Rule;

class UpsertMedicineMonthlyAcquisitionRequest extends BaseApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $unit = $this->input('unit_measure');
        if (is_string($unit) && strtolower(trim($unit)) === 'unit') {
            $this->merge(['unit_measure' => 'un']);
        }
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/pharmacy/monthly-acquisitions');
    }

    public function rules(): array
    {
        return [
            'medicine_item_id' => ['required', 'integer', 'exists:medicine_items,id'],
            'reference_month' => ['required', 'regex:/^\d{4}\-(0[1-9]|1[0-2])$/'],
            'acquired_quantity' => ['required', 'numeric', 'min:0'],
            'unit_measure' => ['required', 'string', 'max:20', Rule::exists('pharmacy_units', 'code')->where('active', true)],
            'source_document' => ['nullable', 'string', 'max:255', Rule::exists('pharmacy_acquisition_sources', 'name')->where('active', true)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'medicine_item_id.required' => 'O medicamento é obrigatório.',
            'medicine_item_id.integer' => 'O identificador do medicamento deve ser um número inteiro.',
            'medicine_item_id.exists' => 'O medicamento informado não foi encontrado.',
            'reference_month.required' => 'O mês de referência é obrigatório.',
            'reference_month.regex' => 'O mês de referência deve estar no formato AAAA-MM.',
            'acquired_quantity.required' => 'A quantidade adquirida é obrigatória.',
            'acquired_quantity.numeric' => 'A quantidade adquirida deve ser numérica.',
            'acquired_quantity.min' => 'A quantidade adquirida não pode ser negativa.',
            'unit_measure.required' => 'A unidade de medida é obrigatória.',
            'unit_measure.string' => 'A unidade de medida deve ser um texto.',
            'unit_measure.max' => 'A unidade de medida deve ter no máximo 20 caracteres.',
            'unit_measure.exists' => 'A unidade de medida informada não está cadastrada no catálogo.',
            'source_document.string' => 'A origem da aquisição deve ser um texto.',
            'source_document.max' => 'A origem da aquisição deve ter no máximo 255 caracteres.',
            'source_document.exists' => 'A origem informada não está cadastrada no catálogo.',
            'note.string' => 'A observação deve ser um texto.',
            'note.max' => 'A observação deve ter no máximo 1000 caracteres.',
        ];
    }
}
