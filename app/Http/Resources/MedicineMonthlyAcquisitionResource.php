<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineMonthlyAcquisitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medicine_item_id' => $this->medicine_item_id,
            'medicine_item' => new MedicineItemResource($this->whenLoaded('medicineItem')),
            'reference_month' => $this->reference_month,
            'acquired_quantity' => $this->acquired_quantity,
            'unit_measure' => $this->unit_measure,
            'source_document' => $this->source_document,
            'note' => $this->note,
            'published_at' => $this->published_at?->toDateTimeString(),
            'updated_by_user_id' => $this->updated_by_user_id,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
