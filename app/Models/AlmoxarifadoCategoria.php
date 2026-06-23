<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoCategoria extends Model
{
    protected $table = 'almoxarifado_categorias';

    protected $fillable = ['nome', 'observacoes', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
