<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsHistorico extends Model
{
    protected $table = 'os_historico';

    public $timestamps = false;

    protected $fillable = ['ordem_servico_id', 'user_id', 'acao', 'status_de', 'status_para', 'observacao'];

    protected $casts = ['created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }
}
