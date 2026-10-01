<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeticaoMotivo extends Model
{
    protected $table = 'peticao_motivos';

    protected $fillable = ['nome', 'descricao', 'unit_id', 'ativo', 'ordem'];

    protected $casts = ['ativo' => 'boolean', 'ordem' => 'integer'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProtocolOrganizationalUnit::class, 'unit_id');
    }
}
