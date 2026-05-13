<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineDailyStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medicine_item_id' => $this->medicine_item_id,
            'medicine_item' => new MedicineItemResource($this->whenLoaded('medicineItem')),
            'reference_date' => $this->reference_date?->toDateString(),
            'availability_status' => $this->availability_status,
            'available_quantity' => $this->available_quantity,
            'restock_forecast_date' => $this->restock_forecast_date?->toDateString(),
            'public_note' => $this->public_note,
            'published_site_at' => $this->published_site_at?->toDateTimeString(),
            'published_panel_at' => $this->published_panel_at?->toDateTimeString(),
            'updated_by_user_id' => $this->updated_by_user_id,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

