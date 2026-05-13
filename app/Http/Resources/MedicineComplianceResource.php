<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineComplianceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'today_reference_date' => $this['today_reference_date'] ?? null,
            'month_reference' => $this['month_reference'] ?? null,
            'daily_updates_days_count' => $this['daily_updates_days_count'] ?? 0,
            'daily_updates_expected_days_count' => $this['daily_updates_expected_days_count'] ?? 0,
            'monthly_acquisitions_count' => $this['monthly_acquisitions_count'] ?? 0,
            'has_today_update' => (bool) ($this['has_today_update'] ?? false),
        ];
    }
}
