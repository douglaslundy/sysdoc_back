<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoEspecie extends Model
{
    protected $table = 'almoxarifado_especies';

    protected $fillable = ['nome', 'observacoes', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
