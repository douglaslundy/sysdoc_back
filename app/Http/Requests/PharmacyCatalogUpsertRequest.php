<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PharmacyCatalogUpsertRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->profile === 'admin';
    }

    public function rules(): array
    {
        $type = $this->route('type');
        $id = $this->route('id');

        if ($type === 'units') {
            return [
                'code' => ['required', 'string', 'max:20', Rule::unique('pharmacy_units', 'code')->ignore($id)],
                'name' => ['required', 'string', 'max:80', Rule::unique('pharmacy_units', 'name')->ignore($id)],
                'active' => ['sometimes', 'boolean'],
            ];
        }

        $table = match ($type) {
            'forms' => 'pharmacy_pharmaceutical_forms',
            'presentations' => 'pharmacy_presentations',
            'sources' => 'pharmacy_acquisition_sources',
            default => null,
        };

        if ($table === null) {
            return ['type' => ['required', Rule::in(['units', 'forms', 'presentations', 'sources'])]];
        }

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique($table, 'name')->ignore($id)],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'O código é obrigatório.',
            'code.unique' => 'Este código já está em uso.',
            'name.required' => 'O nome é obrigatório.',
            'name.unique' => 'Este nome já está em uso.',
            'type.in' => 'O tipo de catálogo é inválido.',
        ];
    }
}
