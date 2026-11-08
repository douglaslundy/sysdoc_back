<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoExameItem extends Model
{
    // Herda a secretaria do pai (sem coluna própria).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('pedido_exame_id', 'pedidos_exame'));
    }

    use HasFactory;

    protected $table = 'pedido_exame_itens';

    protected $fillable = ['pedido_exame_id', 'exame_id'];

    public function pedido()
    {
        return $this->hasOne(PedidoExame::class, 'id', 'pedido_exame_id');
    }

    public function exame()
    {
        return $this->hasOne(Exame::class, 'id', 'exame_id');
    }
}
