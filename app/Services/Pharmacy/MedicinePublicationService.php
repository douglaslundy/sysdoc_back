<?php

namespace App\Services\Pharmacy;

use App\Models\MedicineDailyStatus;
use App\Models\MedicineMonthlyAcquisition;
use App\Models\MedicinePublication;
use App\Services\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class MedicinePublicationService
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = MedicinePublication::query()->orderByDesc('id');

        if (! empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function create(array $data, ?int $userId): MedicinePublication
    {
        $target = $this->resolveReference($data['reference_type'], (int) $data['reference_id']);
        if (! $target) {
            throw new ModelNotFoundException('Referência de publicação não encontrada.');
        }

        $publication = DB::transaction(function () use ($data, $target, $userId) {
            $publication = MedicinePublication::create([
                ...$data,
                'published_by_user_id' => $userId,
            ]);

            $isPublished = $data['status'] === 'published';
            $publishedAt = $data['published_at'] ?? now();

            if ($isPublished && $data['reference_type'] === 'daily') {
                if ($data['channel'] === 'site') {
                    $target->update(['published_site_at' => $publishedAt]);
                }
                if ($data['channel'] === 'panel') {
                    $target->update(['published_panel_at' => $publishedAt]);
                }
            }

            if ($isPublished && $data['reference_type'] === 'monthly') {
                $target->update(['published_at' => $publishedAt]);
            }

            return $publication;
        });

        AuditService::record('CREATE_PUBLICATION', $publication, null, $publication->toArray());

        return $publication;
    }

    public function delete(int $id): void
    {
        $publication = MedicinePublication::find($id);
        if (! $publication) {
            throw new ModelNotFoundException('Publicação não encontrada.');
        }

        AuditService::record('DELETE_PUBLICATION', $publication, $publication->toArray(), null);
        $publication->delete();
    }

    private function resolveReference(string $referenceType, int $referenceId): MedicineDailyStatus|MedicineMonthlyAcquisition|null
    {
        if ($referenceType === 'daily') {
            return MedicineDailyStatus::find($referenceId);
        }

        return MedicineMonthlyAcquisition::find($referenceId);
    }
}
