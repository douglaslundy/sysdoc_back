<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestStatus = $this->relationLoaded('latestDailyStatus') ? $this->getRelation('latestDailyStatus') : null;
        $availabilityStatus = $latestStatus?->availability_status;
        $availableQuantity = $latestStatus?->available_quantity;
        $isAvailable = $availabilityStatus === 'available' && (float) ($availableQuantity ?? 0) > 0;

        return [
            'id' => $this->id,
            'internal_code' => $this->internal_code,
            'brand_name' => $this->brand_name,
            'active_ingredient' => $this->active_ingredient,
            'concentration' => $this->concentration,
            'pharmaceutical_form' => $this->pharmaceutical_form,
            'presentation' => $this->presentation,
            'unit_measure' => $this->unit_measure,
            'ean_code' => $this->ean_code,
            'is_free_distribution' => (bool) $this->is_free_distribution,
            'is_controlled' => (bool) $this->is_controlled,
            'is_judicial_order' => (bool) $this->is_judicial_order,
            'is_high_cost' => (bool) $this->is_high_cost,
            'active' => (bool) $this->active,
            'technical_notes' => $this->technical_notes,
            'availability_status' => $availabilityStatus,
            'available_quantity' => $availableQuantity,
            'is_available' => $isAvailable,
            'restock_forecast_date' => $latestStatus?->restock_forecast_date?->toDateString(),
            'public_note' => $latestStatus?->public_note,
            'last_status_updated_at' => $latestStatus?->updated_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
