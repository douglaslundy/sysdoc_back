<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OuvidoriaHistorico extends Model
{
    protected $table = 'ouvidoria_historico';

    public $timestamps = false;

    protected $fillable = [
        'manifestacao_id', 'user_id', 'acao', 'status_de', 'status_para', 'secretaria_de', 'secretaria_para',
        'observacao', 'interno', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime', 'interno' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }
}
