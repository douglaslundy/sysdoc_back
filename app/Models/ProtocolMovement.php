<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolMovement extends Model
{
    use HasFactory;

    protected $table = 'protocol_movements';

    // Protocolo de pedido de cadastro só para admin e gestor da secretaria (ver o escopo).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('protocol_id', 'protocols', true));
        static::addGlobalScope(new \App\Models\Scopes\CadastroProtocoloScope('protocol_id'));

        // Marca o destinatário que NÃO era da secretaria do protocolo: só ele ganha participação (ParticipacaoProtocolo).
        static::creating(function (self $m) {
            if (! $m->to_user_id) {
                return;
            }
            $membro = \Illuminate\Support\Facades\DB::table('protocols')->where('id', $m->protocol_id)
                ->whereIn('secretaria_id', \Illuminate\Support\Facades\DB::table('user_secretarias')->select('secretaria_id')->where('user_id', $m->to_user_id))
                ->exists();
            if (! $membro) {
                $m->dados = array_merge($m->dados ?? [], ['destinatario_externo' => true]);
            }
        });
    }

    protected $fillable = [
        'protocol_id',
        'from_unit_id',
        'to_unit_id',
        'from_user_id',
        'to_user_id',
        'acao',
        'status_anterior',
        'status_novo',
        'observacao',
        'dados',
        'user_id',
    ];

    protected $casts = [
        'dados' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocol::class, 'protocol_id')->withoutGlobalScope(\App\Models\Scopes\SecretariaScope::class);
    }
}
