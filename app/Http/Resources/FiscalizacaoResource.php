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
                'nome_estabelecimento' => $this->estabelecimento?->nome_estabelecimento ?? $this->estabelecimento_nome_informado,
            ],
            'protocolo' => $this->protocolo,
            'origem' => $this->origem,
            'assunto' => $this->assunto,
            'descricao_denuncia' => $this->descricao_denuncia,
            'local_endereco' => $this->local_endereco,
            'estabelecimento_nome_informado' => $this->estabelecimento_nome_informado,
            'denunciante_nome' => $this->denunciante_nome,
            'denunciante_contato' => $this->denunciante_contato,
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
