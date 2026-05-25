<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estabelecimento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'estabelecimentos';

    protected $fillable = [
        'nome_responsavel',
        'nome_estabelecimento',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'telefone',
        'endereco',
        'obs',
    ];

    public function alvaras(): HasMany
    {
        return $this->hasMany(Alvara::class, 'estabelecimento_id');
    }

    public function cnaes(): BelongsToMany
    {
        return $this->belongsToMany(Cnae::class, 'estabelecimento_cnaes')
            ->withTimestamps();
    }
}
