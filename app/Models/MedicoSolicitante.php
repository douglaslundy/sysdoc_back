<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicoSolicitante extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    use HasFactory;

    protected $table = 'medicos_solicitantes';

    protected $fillable = [
        'secretaria_id',
        'nome', 'crm', 'uf_crm', 'especialidade', 'telefone', 'ativo',
    ];

    public function pedidos()
    {
        return $this->hasMany(PedidoExame::class, 'medico_solicitante_id');
    }
}
