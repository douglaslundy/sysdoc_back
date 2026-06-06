<?php

namespace App\Services\Pharmacy;

use App\Models\MedicineDailyStatus;
use App\Models\MedicineMonthlyAcquisition;

class MedicineComplianceService
{
    public function summary(): array
    {
        $today = now()->toDateString();
        $month = now()->format('Y-m');
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $daysPublished = MedicineDailyStatus::whereBetween('reference_date', [$start, $end])
            ->distinct('reference_date')
            ->count('reference_date');

        $currentMonthAcquisitions = MedicineMonthlyAcquisition::where('reference_month', $month)->count();

        $summary = [
            'today_reference_date' => $today,
            'month_reference' => $month,
            'daily_updates_days_count' => $daysPublished,
            'daily_updates_expected_days_count' => (int) now()->day,
            'monthly_acquisitions_count' => $currentMonthAcquisitions,
            'has_today_update' => MedicineDailyStatus::whereDate('reference_date', $today)->exists(),
        ];

        return $summary;
    }
}
