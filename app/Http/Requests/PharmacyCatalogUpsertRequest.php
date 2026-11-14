<?php

namespace App\Http\Requests;

use App\Support\EscopoAlmoxarifado;
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
        $model = match ($type) {
            'units' => \App\Models\PharmacyUnit::class,
            'forms' => \App\Models\PharmacyPharmaceuticalForm::class,
            'presentations' => \App\Models\PharmacyPresentation::class,
            'sources' => \App\Models\PharmacyAcquisitionSource::class,
            default => null,
        };
        // PUT: a secretaria é a do registro (outra secretaria = 404); POST: a ativa, ou a informada pelo admin em "Todas".
        $sid = $model ? EscopoAlmoxarifado::alvo($model, $id, $this->input('secretaria_id')) : 0;
        $campo = $id === null ? ['secretaria_id' => EscopoAlmoxarifado::regraCampo()] : [];

        if ($type === 'units') {
            return [
                ...$campo,
                'code' => ['required', 'string', 'max:20', EscopoAlmoxarifado::unico('pharmacy_units', 'code', $sid)->ignore($id)],
                'name' => ['required', 'string', 'max:80', EscopoAlmoxarifado::unico('pharmacy_units', 'name', $sid)->ignore($id)],
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
            ...$campo,
            'name' => ['required', 'string', 'max:120', EscopoAlmoxarifado::unico($table, 'name', $sid)->ignore($id)],
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
