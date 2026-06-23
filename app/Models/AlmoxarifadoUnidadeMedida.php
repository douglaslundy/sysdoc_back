<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoUnidadeMedida extends Model
{
    protected $table = 'almoxarifado_unidades_medida';

    protected $fillable = ['nome', 'sigla', 'observacoes', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
