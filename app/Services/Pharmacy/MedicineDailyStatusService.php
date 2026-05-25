<?php

namespace App\Services\Pharmacy;

use App\Models\MedicineDailyStatus;
use App\Models\MedicineItem;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MedicineDailyStatusService
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        if (filter_var($filters['include_all'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return $this->paginateAllMedicines($filters, $perPage);
        }

        $query = MedicineDailyStatus::with('medicineItem')
            ->orderByDesc('reference_date')
            ->orderByDesc('id');

        if (! empty($filters['reference_date'])) {
            $query->whereDate('reference_date', $filters['reference_date']);
        }

        if (! empty($filters['availability_status'])) {
            $query->where('availability_status', $filters['availability_status']);
        }

        if (! empty($filters['medicine_item_id'])) {
            $query->where('medicine_item_id', (int) $filters['medicine_item_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('medicineItem', function ($q) use ($search) {
                $q->where('active_ingredient', 'LIKE', "%{$search}%")
                    ->orWhere('brand_name', 'LIKE', "%{$search}%")
                    ->orWhere('internal_code', 'LIKE', "%{$search}%")
                    ->orWhere('concentration', 'LIKE', "%{$search}%");
            });
        }

        $result = $query->paginate($perPage);

        AuditService::record('VIEW', null, null, [
            'event' => 'LIST_DAILY_STATUSES',
            'filters' => $filters,
            'per_page' => $perPage,
            'total' => $result->total(),
        ]);

        return $result;
    }

    private function paginateAllMedicines(array $filters, int $perPage): LengthAwarePaginator
    {
        $fallbackReferenceDate = $filters['reference_date'] ?? Carbon::today()->toDateString();
        $statusFilter = $filters['availability_status'] ?? null;

        $query = MedicineItem::with(['dailyStatuses' => function ($q) {
                $q->orderByDesc('reference_date')
                    ->orderByDesc('id');
            }])
            ->where('active', true)
            ->orderBy('active_ingredient');

        if ($statusFilter === 'no_record') {
            $query->whereDoesntHave('dailyStatuses', function ($q) use ($fallbackReferenceDate) {
                $q->whereDate('reference_date', $fallbackReferenceDate);
            });
        } elseif ($statusFilter) {
            $query->whereHas('dailyStatuses', function ($q) use ($statusFilter) {
                $q->where('availability_status', $statusFilter);
            });
        }

        if (! empty($filters['medicine_item_id'])) {
            $query->where('id', (int) $filters['medicine_item_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('active_ingredient', 'LIKE', "%{$search}%")
                    ->orWhere('brand_name', 'LIKE', "%{$search}%")
                    ->orWhere('internal_code', 'LIKE', "%{$search}%")
                    ->orWhere('concentration', 'LIKE', "%{$search}%");
            });
        }

        $result = $query->paginate($perPage);
        $result->getCollection()->transform(function (MedicineItem $medicine) use ($fallbackReferenceDate) {
            $status = $medicine->dailyStatuses->first();
            if ($status) {
                $status->setRelation('medicineItem', $medicine);

                return $status;
            }

            $syntheticStatus = new MedicineDailyStatus([
                'medicine_item_id' => $medicine->id,
                'reference_date' => $fallbackReferenceDate,
                'availability_status' => null,
                'available_quantity' => null,
                'restock_forecast_date' => null,
                'public_note' => null,
            ]);
            $syntheticStatus->exists = false;
            $syntheticStatus->setRelation('medicineItem', $medicine);

            return $syntheticStatus;
        });

        AuditService::record('VIEW', null, null, [
            'event' => 'LIST_DAILY_STATUSES_ALL_MEDICINES',
            'filters' => $filters,
            'per_page' => $perPage,
            'total' => $result->total(),
        ]);

        return $result;
    }

    public function upsert(array $data, ?int $userId): MedicineDailyStatus
    {
        $data['updated_by_user_id'] = $userId;

        $status = MedicineDailyStatus::updateOrCreate(
            [
                'medicine_item_id' => $data['medicine_item_id'],
                'reference_date' => $data['reference_date'],
            ],
            $data
        );
        $status->load('medicineItem');

        AuditService::record('UPSERT_DAILY_STATUS', $status, null, $status->toArray());

        return $status;
    }

    public function update(int $id, array $data, ?int $userId): MedicineDailyStatus
    {
        $status = $this->findOrFail($id);
        $old = $status->toArray();

        $status->update([
            ...$data,
            'updated_by_user_id' => $userId,
        ]);
        $status->load('medicineItem');

        AuditService::record('UPDATE_DAILY_STATUS', $status, $old, $status->toArray());

        return $status;
    }

    public function delete(int $id): void
    {
        $status = $this->findOrFail($id);
        AuditService::record('DELETE_DAILY_STATUS', $status, $status->toArray(), null);
        $status->delete();
    }

    private function findOrFail(int $id): MedicineDailyStatus
    {
        $status = MedicineDailyStatus::find($id);
        if (! $status) {
            throw new ModelNotFoundException('Status diÃ¡rio nÃ£o encontrado.');
        }

        return $status;
    }
}
