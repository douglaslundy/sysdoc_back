<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanTask extends Model
{
    use HasFactory;

    protected $table = 'kanban_tasks';

    // Protocolo de pedido de cadastro só para admin e gestor da secretaria (ver o escopo).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\CadastroProtocoloScope('protocol_id'));
    }

    protected $fillable = [
        'protocol_id',
        'unit_id',
        'fiscalizacao_id',
        'created_by_id',
        'secretaria_id',
        'updated_by_id',
        'responsavel_id',
        'visibility',
        'titulo',
        'descricao',
        'status',
        'prioridade',
        'vencimento',
        'ordem',
        'concluido_at',
        'arquivado_at',
    ];

    protected $casts = [
        'vencimento' => 'date',
        'concluido_at' => 'datetime',
        'arquivado_at' => 'datetime',
    ];

    /** Card de pedido de cadastro (consulta sem escopo: vale mesmo se o chamador não o enxergar). */
    public function isCadastro(): bool
    {
        return $this->protocol_id !== null
            && Protocol::withoutGlobalScopes()->whereKey($this->protocol_id)->where('tipo', \App\Models\Scopes\CadastroProtocoloScope::TIPO)->exists();
    }

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocol::class, 'protocol_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProtocolOrganizationalUnit::class, 'unit_id');
    }

    public function fiscalizacao(): BelongsTo
    {
        return $this->belongsTo(Fiscalizacao::class, 'fiscalizacao_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }
}
