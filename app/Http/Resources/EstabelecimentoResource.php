<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstabelecimentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'nome_responsavel'     => $this->nome_responsavel,
            'nome_estabelecimento' => $this->nome_estabelecimento,
            'endereco'             => $this->endereco,
            'cnaes'                => $this->cnaes,
            'alvaras_count'        => $this->whenCounted('alvaras'),
            'created_at'           => $this->created_at?->toDateTimeString(),
            'updated_at'           => $this->updated_at?->toDateTimeString(),
        ];
    }
}
