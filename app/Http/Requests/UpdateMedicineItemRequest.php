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
            'active' => ['sometimes', 'boolean'],
            'technical_notes' => ['nullable', 'string', 'max:4000'],
        ];
    }

    public function messages(): array
    {
        return [
            'internal_code.required' => 'O cÃ³digo interno Ã© obrigatÃ³rio.',
            'internal_code.string' => 'O cÃ³digo interno deve ser um texto.',
            'internal_code.max' => 'O cÃ³digo interno deve ter no mÃ¡ximo 80 caracteres.',
            'internal_code.unique' => 'JÃ¡ existe um medicamento com este cÃ³digo interno.',
            'brand_name.string' => 'O nome comercial deve ser um texto.',
            'brand_name.max' => 'O nome comercial deve ter no mÃ¡ximo 255 caracteres.',
            'active_ingredient.required' => 'O princÃ­pio ativo Ã© obrigatÃ³rio.',
            'active_ingredient.string' => 'O princÃ­pio ativo deve ser um texto.',
            'active_ingredient.max' => 'O princÃ­pio ativo deve ter no mÃ¡ximo 255 caracteres.',
            'concentration.required' => 'A concentraÃ§Ã£o Ã© obrigatÃ³ria.',
            'concentration.string' => 'A concentraÃ§Ã£o deve ser um texto.',
            'concentration.max' => 'A concentraÃ§Ã£o deve ter no mÃ¡ximo 100 caracteres.',
            'pharmaceutical_form.required' => 'A forma farmacÃªutica Ã© obrigatÃ³ria.',
            'pharmaceutical_form.string' => 'A forma farmacÃªutica deve ser um texto.',
            'pharmaceutical_form.max' => 'A forma farmacÃªutica deve ter no mÃ¡ximo 120 caracteres.',
            'pharmaceutical_form.exists' => 'A forma farmacÃªutica informada nÃ£o estÃ¡ cadastrada no catÃ¡logo.',
            'presentation.required' => 'A apresentaÃ§Ã£o Ã© obrigatÃ³ria.',
            'presentation.string' => 'A apresentaÃ§Ã£o deve ser um texto.',
            'presentation.max' => 'A apresentaÃ§Ã£o deve ter no mÃ¡ximo 120 caracteres.',
            'presentation.exists' => 'A apresentaÃ§Ã£o informada nÃ£o estÃ¡ cadastrada no catÃ¡logo.',
            'unit_measure.required' => 'A unidade de medida Ã© obrigatÃ³ria.',
            'unit_measure.string' => 'A unidade de medida deve ser um texto.',
            'unit_measure.max' => 'A unidade de medida deve ter no mÃ¡ximo 20 caracteres.',
            'unit_measure.exists' => 'A unidade de medida informada nÃ£o estÃ¡ cadastrada no catÃ¡logo.',
            'ean_code.string' => 'O cÃ³digo EAN deve ser um texto.',
            'ean_code.max' => 'O cÃ³digo EAN deve ter no mÃ¡ximo 50 caracteres.',
            'is_free_distribution.boolean' => 'O campo distribuiÃ§Ã£o gratuita deve ser verdadeiro ou falso.',
            'is_controlled.boolean' => 'O campo medicamento controlado deve ser verdadeiro ou falso.',
            'active.boolean' => 'O campo ativo deve ser verdadeiro ou falso.',
            'technical_notes.string' => 'As observaÃ§Ãµes tÃ©cnicas devem ser um texto.',
            'technical_notes.max' => 'As observaÃ§Ãµes tÃ©cnicas devem ter no mÃ¡ximo 4000 caracteres.',
        ];
    }
}
