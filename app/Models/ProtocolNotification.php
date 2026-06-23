<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolNotification extends Model
{
    use HasFactory;

    protected $table = 'protocol_notifications';

    protected $fillable = [
        'protocol_id',
        'user_id',
        'canal',
        'titulo',
        'mensagem',
        'status_envio',
        'lida_em',
        'enviada_em',
        'erro',
        'dados',
    ];

    protected $casts = [
        'lida_em' => 'datetime',
        'enviada_em' => 'datetime',
        'dados' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
