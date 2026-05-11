<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlvaraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'numero_alvara'       => $this->numero_alvara,
            'nivel_risco'         => $this->nivel_risco,
            'estabelecimento_id'  => $this->estabelecimento_id,
            'estabelecimento'     => new EstabelecimentoResource($this->whenLoaded('estabelecimento')),
            'data_alvara'         => $this->data_alvara?->toDateString(),
            'vencimento_alvara'   => $this->vencimento_alvara?->toDateString(),
            'contato'             => $this->contato,
            'created_at'          => $this->created_at?->toDateTimeString(),
            'updated_at'          => $this->updated_at?->toDateTimeString(),
        ];
    }
}
