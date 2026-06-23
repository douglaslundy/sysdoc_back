<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoSecretaria extends Model
{
    protected $table = 'almoxarifado_secretarias';

    protected $fillable = ['nome', 'sigla', 'responsavel', 'contato', 'observacoes', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
