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
        $query = MedicineItem::query()
            ->with(['latestDailyStatus' => function ($statusQuery) {
                $statusQuery->orderByDesc('reference_date')->orderByDesc('id');
            }])
            ->orderBy('active_ingredient');

        $this->applyFilters($query, $filters);

        $result = $query->paginate($perPage);

        return $result;
    }

    private function applyFilters($query, array $filters): void
    {
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

        if (! array_key_exists('availability_status', $filters) || $filters['availability_status'] === null || $filters['availability_status'] === '') {
            return;
        }

        $availabilityStatus = (string) $filters['availability_status'];

        $query->where(function ($statusQuery) use ($availabilityStatus) {
            if ($availabilityStatus === 'available') {
                $statusQuery->whereHas('latestDailyStatus', function ($latestQuery) {
                    $latestQuery->where('availability_status', 'available')
                        ->where(function ($quantityQuery) {
                            $quantityQuery->whereNull('available_quantity')
                                ->orWhere('available_quantity', '>', 0);
                        });
                });

                return;
            }

            $statusQuery->whereDoesntHave('latestDailyStatus')
                ->orWhereHas('latestDailyStatus', function ($latestQuery) {
                    $latestQuery->where(function ($availabilityQuery) {
                        $availabilityQuery->where('availability_status', 'unavailable')
                            ->orWhereNull('availability_status')
                            ->orWhere(function ($quantityQuery) {
                                $quantityQuery->whereNotNull('available_quantity')
                                    ->where('available_quantity', '<=', 0);
                            });
                    });
                });
        });
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
            throw new ModelNotFoundException('Medicamento não encontrado.');
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
