<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'id_client' => $this->id_client,
            'id_specialities' => $this->id_specialities,
            'id_user' => $this->id_user,
            'position' => $this->done ? 0 : (int) ($this->position ?? 0),
            'done' => (int) $this->done,
            'urgency' => (int) $this->urgency,
            'date_of_realized' => $this->date_of_realized,
            'obs' => $this->obs,
            'created_at' => $this->created_at,
            'attachments_count' => (int) ($this->attachments_count ?? 0),
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client?->id,
                'name' => $this->client?->name,
                'mother' => $this->client?->mother,
                'cpf' => $this->client?->cpf,
                'cns' => $this->client?->cns,
                'phone' => $this->client?->phone,
            ]),
            'speciality' => $this->whenLoaded('speciality', fn () => [
                'id' => $this->speciality?->id,
                'name' => $this->speciality?->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),
        ];
    }
}
