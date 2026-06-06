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
            'father' => $this->father,
            'cpf' => $this->cpf,
            'cns' => $this->cns,
            'phone' => $this->phone,
            'email' => $this->email,
            'obs' => $this->obs,
            'born_date' => $this->born_date,
            'sexo' => $this->sexo,
            'raca_cor' => $this->raca_cor,
            'data_obito' => $this->data_obito,
            'active' => $this->active,
            'st_falecido' => $this->st_falecido,
            'addresses' => $this->whenLoaded('addresses'),
        ];
    }
}
