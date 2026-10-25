<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IluminacaoPosteResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'protocolo' => $this->protocolo,
            'ponto_referencia' => $this->ponto_referencia,
            'logradouro' => $this->logradouro,
            'numero' => $this->numero,
            'bairro' => $this->bairro,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'precisao_m' => $this->precisao_m,
            'status' => $this->status,
            'concluido_em' => $this->concluido_em,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'cadastrado_por' => $this->cadastrador ? ['id' => $this->cadastrador->id, 'name' => $this->cadastrador->name] : null,
            'fotos_count' => $this->fotos_count ?? 0,
            // Sem path/disk: o arquivo só sai pelo download autenticado.
            'fotos' => $this->whenLoaded('fotos', fn () => $this->fotos->map(fn ($f) => [
                'id' => $f->id,
                'momento' => $f->momento,
                'mime_type' => $f->mime_type,
                'size_bytes' => $f->size_bytes,
                'movimentacao_id' => $f->movimentacao_id,
            ])->values()),
            'pode_editar' => $user ? $this->editavelPor($user) : false,
        ];
    }
}
