<?php

namespace App\Services\Pharmacy;

use App\Models\MedicineItem;
use App\Services\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MedicineItemService
{
    public function queryForSelect(array $filters)
    {
        $query = MedicineItem::query()->orderBy('active_ingredient');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('active_ingredient', 'LIKE', "%{$search}%")
                    ->orWhere('internal_code', 'LIKE', "%{$search}%");
            });
        }

        if (array_key_exists('active', $filters)) {
            $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = MedicineItem::query()->orderBy('active_ingredient');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('active_ingredient', 'LIKE', "%{$search}%")
                    ->orWhere('brand_name', 'LIKE', "%{$search}%")
                    ->orWhere('internal_code', 'LIKE', "%{$search}%");
            });
        }

        if (array_key_exists('active', $filters)) {
            $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (array_key_exists('is_free_distribution', $filters)) {
            $query->where('is_free_distribution', filter_var($filters['is_free_distribution'], FILTER_VALIDATE_BOOLEAN));
        }

        if (array_key_exists('is_controlled', $filters)) {
            $query->where('is_controlled', filter_var($filters['is_controlled'], FILTER_VALIDATE_BOOLEAN));
        }

        if (array_key_exists('is_judicial_order', $filters)) {
            $query->where('is_judicial_order', filter_var($filters['is_judicial_order'], FILTER_VALIDATE_BOOLEAN));
        }

        if (array_key_exists('is_high_cost', $filters)) {
            $query->where('is_high_cost', filter_var($filters['is_high_cost'], FILTER_VALIDATE_BOOLEAN));
        }

        $result = $query->paginate($perPage);

        AuditService::record('VIEW', null, null, [
            'event' => 'LIST_MEDICINES',
            'filters' => $filters,
            'per_page' => $perPage,
            'total' => $result->total(),
        ]);

        return $result;
    }

    public function create(array $data): MedicineItem
    {
        $medicine = MedicineItem::create($data);
        AuditService::record('CREATE', $medicine, null, $medicine->toArray());

        return $medicine;
    }

    public function findOrFail(int $id): MedicineItem
    {
        $medicine = MedicineItem::find($id);
        if (! $medicine) {
            throw new ModelNotFoundException('Medicamento nÃ£o encontrado.');
        }

        return $medicine;
    }

    public function show(int $id): MedicineItem
    {
        $medicine = $this->findOrFail($id);
        AuditService::record('VIEW', $medicine, null, ['internal_code' => $medicine->internal_code]);

        return $medicine;
    }

    public function update(int $id, array $data): MedicineItem
    {
        $medicine = $this->findOrFail($id);
        $old = $medicine->toArray();
        $medicine->update($data);
        AuditService::record('UPDATE', $medicine, $old, $medicine->toArray());

        return $medicine;
    }

    public function delete(int $id): void
    {
        $medicine = $this->findOrFail($id);
        AuditService::record('DELETE', $medicine, $medicine->toArray(), null);
        $medicine->delete();
    }
}
