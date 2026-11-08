<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoFornecedor extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    protected $table = 'almoxarifado_fornecedores';

    protected $fillable = [
        'secretaria_id',
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
