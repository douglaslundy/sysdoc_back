<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoUnidadeMedida extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    protected $table = 'almoxarifado_unidades_medida';

    protected $fillable = ['secretaria_id', 'nome', 'sigla', 'observacoes', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
