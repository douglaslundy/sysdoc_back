<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeticaoMotivo extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    protected $table = 'peticao_motivos';

    protected $fillable = ['secretaria_id', 'nome', 'descricao', 'unit_id', 'ativo', 'ordem'];

    protected $casts = ['ativo' => 'boolean', 'ordem' => 'integer'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProtocolOrganizationalUnit::class, 'unit_id');
    }
}
