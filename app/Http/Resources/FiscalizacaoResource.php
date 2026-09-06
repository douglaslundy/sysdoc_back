<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FiscalizacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estabelecimento_id' => $this->estabelecimento_id,
            'estabelecimento' => [
                'id' => $this->estabelecimento?->id,
                'nome_estabelecimento' => $this->estabelecimento?->nome_estabelecimento,
            ],
            'fiscal_id' => $this->fiscal_id,
            'fiscal' => [
                'id' => $this->fiscal?->id,
                'name' => $this->fiscal?->name,
            ],
            'data_visita' => $this->data_visita?->toDateString(),
            'resultado' => $this->resultado,
            'observacoes' => $this->observacoes,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
