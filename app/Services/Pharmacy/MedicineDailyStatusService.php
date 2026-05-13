<?php

namespace App\Services\Pharmacy;

use App\Models\MedicineDailyStatus;
use App\Services\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MedicineDailyStatusService
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
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

        $result = $query->paginate($perPage);

        AuditService::record('VIEW', null, null, [
            'event' => 'LIST_DAILY_STATUSES',
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
