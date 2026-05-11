<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estabelecimento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'estabelecimentos';

    protected $fillable = [
        'nome_responsavel',
        'nome_estabelecimento',
        'endereco',
        'cnaes',
    ];

    public function alvaras(): HasMany
    {
        return $this->hasMany(Alvara::class, 'estabelecimento_id');
    }
}
