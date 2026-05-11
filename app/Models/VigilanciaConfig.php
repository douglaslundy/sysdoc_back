<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VigilanciaConfig extends Model
{
    protected $table = 'vigilancia_configs';

    protected $fillable = [
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

    public static function get(): self
    {
        return static::firstOrCreate([], [
            'grant_type' => 'ALVARÁ SANITÁRIO DE FUNCIONAMENTO',
        ]);
    }
}
