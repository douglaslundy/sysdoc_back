<?php

namespace App\Services\Pharmacy;

use App\Models\MedicineMonthlyAcquisition;
use App\Services\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MedicineMonthlyAcquisitionService
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = MedicineMonthlyAcquisition::with('medicineItem')
            ->orderByDesc('reference_month')
            ->orderByDesc('id');

        if (! empty($filters['reference_month'])) {
            $query->where('reference_month', $filters['reference_month']);
        }

        if (! empty($filters['medicine_item_id'])) {
            $query->where('medicine_item_id', (int) $filters['medicine_item_id']);
        }

        $result = $query->paginate($perPage);

        AuditService::record('VIEW', null, null, [
            'event' => 'LIST_MONTHLY_ACQUISITIONS',
            'filters' => $filters,
            'per_page' => $perPage,
            'total' => $result->total(),
        ]);

        return $result;
    }

    public function upsert(array $data, ?int $userId): MedicineMonthlyAcquisition
    {
        $data['updated_by_user_id'] = $userId;

        $acquisition = MedicineMonthlyAcquisition::updateOrCreate(
            [
                'medicine_item_id' => $data['medicine_item_id'],
                'reference_month' => $data['reference_month'],
            ],
            $data
        );
        $acquisition->load('medicineItem');

        AuditService::record('UPSERT_MONTHLY_ACQUISITION', $acquisition, null, $acquisition->toArray());

        return $acquisition;
    }

    public function update(int $id, array $data, ?int $userId): MedicineMonthlyAcquisition
    {
        $acquisition = $this->findOrFail($id);
        $old = $acquisition->toArray();

        $acquisition->update([
            ...$data,
            'updated_by_user_id' => $userId,
        ]);
        $acquisition->load('medicineItem');

        AuditService::record('UPDATE_MONTHLY_ACQUISITION', $acquisition, $old, $acquisition->toArray());

        return $acquisition;
    }

    public function delete(int $id): void
    {
        $acquisition = $this->findOrFail($id);
        AuditService::record('DELETE_MONTHLY_ACQUISITION', $acquisition, $acquisition->toArray(), null);
        $acquisition->delete();
    }

    private function findOrFail(int $id): MedicineMonthlyAcquisition
    {
        $acquisition = MedicineMonthlyAcquisition::find($id);
        if (! $acquisition) {
            throw new ModelNotFoundException('AquisiÃ§Ã£o mensal nÃ£o encontrada.');
        }

        return $acquisition;
    }
}
