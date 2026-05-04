<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoExame extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pedidos_exame';

    protected $fillable = [
        'client_id', 'criado_por', 'medico_solicitante',
        'data_pedido', 'data_coleta', 'status', 'observacoes',
    ];

    public function cliente()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function itens()
    {
        return $this->hasMany(PedidoExameItem::class, 'pedido_exame_id', 'id');
    }

    public function exames()
    {
        return $this->belongsToMany(Exame::class, 'pedido_exame_itens', 'pedido_exame_id', 'exame_id');
    }

    public function resultado()
    {
        return $this->hasOne(ResultadoExame::class, 'pedido_exame_id', 'id');
    }

    public function criadoPor()
    {
        return $this->hasOne(User::class, 'id', 'criado_por');
    }
}
