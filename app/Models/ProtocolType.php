<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProtocolType extends Model
{
    protected $table = 'protocol_types';

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'ordem' => 'integer',
        'ativo' => 'boolean',
    ];
}
