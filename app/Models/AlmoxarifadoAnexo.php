<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AlmoxarifadoAnexo extends Model
{
    protected $table = 'almoxarifado_arquivos';

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'nome_original',
        'caminho',
        'mime_type',
        'tamanho_bytes',
        'tipo',
        'user_id',
    ];

    protected $casts = [
        'tamanho_bytes' => 'integer',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
