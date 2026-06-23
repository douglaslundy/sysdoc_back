<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProtocolAlert extends Model
{
    use HasFactory;

    protected $table = 'protocol_alerts';

    protected $fillable = [
        'nome',
        'descricao',
        'modulo',
        'gatilho',
        'condicoes',
        'canais',
        'destinatarios',
        'template',
        'ativo',
        'frequencia',
        'prevenir_duplicidade',
    ];

    protected $casts = [
        'condicoes' => 'array',
        'canais' => 'array',
        'destinatarios' => 'array',
        'ativo' => 'boolean',
        'prevenir_duplicidade' => 'boolean',
    ];
}
