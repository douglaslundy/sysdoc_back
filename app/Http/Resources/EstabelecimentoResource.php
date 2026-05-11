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
            'razao_social'         => $this->razao_social,
            'nome_fantasia'        => $this->nome_fantasia,
            'cnpj'                 => $this->cnpj,
            'telefone'             => $this->telefone,
            'endereco'             => $this->endereco,
            'cnaes'                => $this->cnaes,
            'obs'                  => $this->obs,
            'alvaras_count'        => $this->whenCounted('alvaras'),
            'created_at'           => $this->created_at?->toDateTimeString(),
            'updated_at'           => $this->updated_at?->toDateTimeString(),
        ];
    }
}
