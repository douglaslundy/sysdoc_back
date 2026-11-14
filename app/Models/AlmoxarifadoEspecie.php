<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoEspecie extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    protected $table = 'almoxarifado_especies';

    protected $fillable = ['secretaria_id', 'nome', 'observacoes', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
