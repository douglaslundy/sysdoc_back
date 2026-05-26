<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Validation\Rule;

class UpdateMedicineItemRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/pharmacy/medicines');
    }

    public function rules(): array
    {
        $medicineId = $this->route('medicine') ?? $this->route('id');

        return [
            'internal_code' => ['sometimes', 'required', 'string', 'max:80', Rule::unique('medicine_items', 'internal_code')->ignore($medicineId)],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'active_ingredient' => ['sometimes', 'required', 'string', 'max:255'],
            'concentration' => ['sometimes', 'required', 'string', 'max:100'],
            'pharmaceutical_form' => ['sometimes', 'required', 'string', 'max:120', Rule::exists('pharmacy_pharmaceutical_forms', 'name')->where('active', true)],
            'presentation' => ['sometimes', 'required', 'string', 'max:120', Rule::exists('pharmacy_presentations', 'name')->where('active', true)],
            'unit_measure' => ['sometimes', 'required', 'string', 'max:20', Rule::exists('pharmacy_units', 'code')->where('active', true)],
            'ean_code' => ['nullable', 'string', 'max:50'],
            'is_free_distribution' => ['sometimes', 'boolean'],
            'is_controlled' => ['sometimes', 'boolean'],
            'is_judicial_order' => ['sometimes', 'boolean'],
            'is_high_cost' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
            'technical_notes' => ['nullable', 'string', 'max:4000'],
        ];
    }

    public function messages(): array
    {
        return [
            'internal_code.required' => 'O código interno é obrigatório.',
            'internal_code.string' => 'O código interno deve ser um texto.',
            'internal_code.max' => 'O código interno deve ter no máximo 80 caracteres.',
            'internal_code.unique' => 'Já existe um medicamento com este código interno.',
            'brand_name.string' => 'O nome comercial deve ser um texto.',
            'brand_name.max' => 'O nome comercial deve ter no máximo 255 caracteres.',
            'active_ingredient.required' => 'O princípio ativo é obrigatório.',
            'active_ingredient.string' => 'O princípio ativo deve ser um texto.',
            'active_ingredient.max' => 'O princípio ativo deve ter no máximo 255 caracteres.',
            'concentration.required' => 'A concentração é obrigatória.',
            'concentration.string' => 'A concentração deve ser um texto.',
            'concentration.max' => 'A concentração deve ter no máximo 100 caracteres.',
            'pharmaceutical_form.required' => 'A forma farmacêutica é obrigatória.',
            'pharmaceutical_form.string' => 'A forma farmacêutica deve ser um texto.',
            'pharmaceutical_form.max' => 'A forma farmacêutica deve ter no máximo 120 caracteres.',
            'pharmaceutical_form.exists' => 'A forma farmacêutica informada não está cadastrada no catálogo.',
            'presentation.required' => 'A apresentação é obrigatória.',
            'presentation.string' => 'A apresentação deve ser um texto.',
            'presentation.max' => 'A apresentação deve ter no máximo 120 caracteres.',
            'presentation.exists' => 'A apresentação informada não está cadastrada no catálogo.',
            'unit_measure.required' => 'A unidade de medida é obrigatória.',
            'unit_measure.string' => 'A unidade de medida deve ser um texto.',
            'unit_measure.max' => 'A unidade de medida deve ter no máximo 20 caracteres.',
            'unit_measure.exists' => 'A unidade de medida informada não está cadastrada no catálogo.',
            'ean_code.string' => 'O código EAN deve ser um texto.',
            'ean_code.max' => 'O código EAN deve ter no máximo 50 caracteres.',
            'is_free_distribution.boolean' => 'O campo distribuição gratuita deve ser verdadeiro ou falso.',
            'is_controlled.boolean' => 'O campo medicamento controlado deve ser verdadeiro ou falso.',
            'is_high_cost.boolean' => 'O campo alto custo deve ser verdadeiro ou falso.',
            'active.boolean' => 'O campo ativo deve ser verdadeiro ou falso.',
            'technical_notes.string' => 'As observações técnicas devem ser um texto.',
            'technical_notes.max' => 'As observações técnicas devem ter no máximo 4000 caracteres.',
        ];
    }
}
