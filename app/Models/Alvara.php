<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alvara extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alvaras';

    protected $fillable = [
        'numero_alvara',
        'nivel_risco',
        'status',
        'estabelecimento_id',
        'data_alvara',
        'vencimento_alvara',
        'contato',
    ];

    protected $attributes = [
        'status' => 'Não requerido',
    ];

    protected $casts = [
        'data_alvara'       => 'date:Y-m-d',
        'vencimento_alvara' => 'date:Y-m-d',
    ];

    public function estabelecimento(): BelongsTo
    {
        return $this->belongsTo(Estabelecimento::class, 'estabelecimento_id');
    }
}
