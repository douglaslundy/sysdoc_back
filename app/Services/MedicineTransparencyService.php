<?php

namespace App\Services;

use App\Models\MedicineDailyStatus;
use App\Models\MedicineMonthlyAcquisition;
use App\Services\AuditService;
use Carbon\Carbon;

class MedicineTransparencyService
{
    public function getPublicDailyList(?string $date = null): array
    {
        $referenceDate = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        $rows = MedicineDailyStatus::with('medicineItem')
            ->whereDate('reference_date', $referenceDate)
            ->whereHas('medicineItem', fn($q) => $q->where('active', true))
            ->orderByDesc('availability_status')
            ->orderBy('reference_date')
            ->get();

        $result = [
            'reference_date' => $referenceDate,
            'last_update_at' => $this->formatLastUpdate($rows->max('updated_at')),
            'items' => $rows->map(function (MedicineDailyStatus $row) {
                return [
                    'medicine_id' => $row->medicine_item_id,
                    'internal_code' => $row->medicineItem?->internal_code,
                    'brand_name' => $row->medicineItem?->brand_name,
                    'active_ingredient' => $row->medicineItem?->active_ingredient,
                    'concentration' => $row->medicineItem?->concentration,
                    'pharmaceutical_form' => $row->medicineItem?->pharmaceutical_form,
                    'presentation' => $row->medicineItem?->presentation,
                    'unit_measure' => $row->medicineItem?->unit_measure,
                    'is_free_distribution' => (bool) $row->medicineItem?->is_free_distribution,
                    'availability_status' => $row->availability_status,
                    'available_quantity' => $row->available_quantity,
                    'restock_forecast_date' => $row->restock_forecast_date?->toDateString(),
                    'public_note' => $row->public_note,
                ];
            })->values(),
        ];

        AuditService::record('VIEW_PUBLIC_MEDICINES_DAILY', null, null, [
            'reference_date' => $referenceDate,
            'items_count' => count($result['items']),
        ]);

        return $result;
    }

    public function getPublicMonthlyAcquisitions(?string $month = null): array
    {
        $referenceMonth = $month ?: now()->format('Y-m');

        $rows = MedicineMonthlyAcquisition::with('medicineItem')
            ->where('reference_month', $referenceMonth)
            ->whereHas('medicineItem', fn($q) => $q->where('active', true))
            ->orderBy('reference_month')
            ->get();

        $result = [
            'reference_month' => $referenceMonth,
            'last_update_at' => $this->formatLastUpdate($rows->max('updated_at')),
            'items' => $rows->map(function (MedicineMonthlyAcquisition $row) {
                return [
                    'medicine_id' => $row->medicine_item_id,
                    'internal_code' => $row->medicineItem?->internal_code,
                    'brand_name' => $row->medicineItem?->brand_name,
                    'active_ingredient' => $row->medicineItem?->active_ingredient,
                    'acquired_quantity' => $row->acquired_quantity,
                    'unit_measure' => $row->unit_measure,
                    'source_document' => $row->source_document,
                    'note' => $row->note,
                ];
            })->values(),
        ];

        AuditService::record('VIEW_PUBLIC_MEDICINES_MONTHLY', null, null, [
            'reference_month' => $referenceMonth,
            'items_count' => count($result['items']),
        ]);

        return $result;
    }

    private function formatLastUpdate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return Carbon::parse($value)->toDateTimeString();
    }
}
