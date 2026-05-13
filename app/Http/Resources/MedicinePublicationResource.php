<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicinePublicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'channel' => $this->channel,
            'status' => $this->status,
            'payload_summary' => $this->payload_summary,
            'published_at' => $this->published_at?->toDateTimeString(),
            'published_by_user_id' => $this->published_by_user_id,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
