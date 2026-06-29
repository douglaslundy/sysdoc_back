<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanTask extends Model
{
    use HasFactory;

    protected $table = 'kanban_tasks';

    protected $fillable = [
        'protocol_id',
        'created_by_id',
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

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocol::class, 'protocol_id');
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
