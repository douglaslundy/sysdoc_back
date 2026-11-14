<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoSecretaria extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    protected $table = 'almoxarifado_secretarias';

    protected $fillable = ['secretaria_id', 'nome', 'sigla', 'responsavel', 'contato', 'observacoes', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
