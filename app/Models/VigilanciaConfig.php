<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VigilanciaConfig extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    protected $table = 'vigilancia_configs';

    protected $fillable = [
        'secretaria_id',
        'estado',
        'nome_municipio',
        'nome_prefeitura',
        'cnpj_prefeitura',
        'nome_secretaria',
        'cnpj_secretaria',
        'divisao',
        'endereco',
        'cep',
        'telefone',
        'email',
        'nome_responsavel',
        'cargo_responsavel',
        'grant_type',
        'observacoes',
    ];

    protected $casts = ['observacoes' => 'array'];

    /** Config da secretaria ativa (ou da informada: admin em "Todas", PDF do alvará). */
    public static function get(?int $secretariaId = null): self
    {
        return static::withoutSecretaria()->firstOrCreate(['secretaria_id' => $secretariaId ?? \App\Support\EscopoAlmoxarifado::id()], [
            'grant_type' => 'ALVARÁ SANITÁRIO DE FUNCIONAMENTO',
        ]);
    }
}
