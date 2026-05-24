<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mother' => $this->mother,
            'cpf' => $this->cpf,
            'cns' => $this->cns,
            'phone' => $this->phone,
            'born_date' => $this->born_date,
            'addresses' => $this->whenLoaded('addresses'),
        ];
    }
}
