<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OsTipoServico extends Model
{
    protected $table = 'os_tipos_servico';

    protected $fillable = ['secretaria_id', 'nome', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
