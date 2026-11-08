<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoLocalizacao extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    protected $table = 'almoxarifado_localizacoes';

    protected $fillable = [
        'secretaria_id',
        'nome',
        'almoxarifado',
        'sala',
        'corredor',
        'estante',
        'prateleira',
        'gaveta',
        'caixa',
        'posicao',
        'observacoes',
        'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];
}
