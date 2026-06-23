<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoLocalizacao extends Model
{
    protected $table = 'almoxarifado_localizacoes';

    protected $fillable = [
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
