<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoFornecedor extends Model
{
    protected $table = 'almoxarifado_fornecedores';

    protected $fillable = [
        'nome',
        'documento',
        'telefone',
        'email',
        'contato',
        'endereco',
        'observacoes',
        'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];
}
