<?php

namespace App\Services\Pharmacy;

use App\Models\PharmacyAcquisitionSource;
use App\Models\PharmacyPharmaceuticalForm;
use App\Models\PharmacyPresentation;
use App\Models\PharmacyUnit;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PharmacyCatalogAdminService
{
    public function list(string $type)
    {
        [$model] = $this->resolve($type);

        return $model::query()->orderBy('name')->get();
    }

    public function store(string $type, array $data): array
    {
        [$model, $isUnit] = $this->resolve($type);
        $payload = $this->sanitize($data, $isUnit);

        $row = $model::create($payload);
        AuditService::record('CREATE_PHARMACY_CATALOG_ITEM', $row, null, $row->toArray());

        return $row->toArray();
    }

    public function update(string $type, int $id, array $data): array
    {
        [$model, $isUnit] = $this->resolve($type);
        $row = $model::find($id);
        if (! $row) {
            throw new ModelNotFoundException('Registro de catálogo não encontrado.');
        }

        $old = $row->toArray();
        $row->update($this->sanitize($data, $isUnit));
        AuditService::record('UPDATE_PHARMACY_CATALOG_ITEM', $row, $old, $row->toArray());

        return $row->toArray();
    }

    public function destroy(string $type, int $id): void
    {
        [$model] = $this->resolve($type);
        $row = $model::find($id);
        if (! $row) {
            throw new ModelNotFoundException('Registro de catálogo não encontrado.');
        }

        AuditService::record('DELETE_PHARMACY_CATALOG_ITEM', $row, $row->toArray(), null);
        $row->delete();
    }

    private function resolve(string $type): array
    {
        return match ($type) {
            'units' => [PharmacyUnit::class, true],
            'forms' => [PharmacyPharmaceuticalForm::class, false],
            'presentations' => [PharmacyPresentation::class, false],
            'sources' => [PharmacyAcquisitionSource::class, false],
            default => throw new ModelNotFoundException('Tipo de catálogo inválido.'),
        };
    }

    private function sanitize(array $data, bool $isUnit): array
    {
        $payload = [
            'name' => trim((string) ($data['name'] ?? '')),
            'active' => (bool) ($data['active'] ?? true),
        ];

        if ($isUnit) {
            $payload['code'] = trim((string) ($data['code'] ?? ''));
        }

        return $payload;
    }
}
