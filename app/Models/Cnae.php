<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cnae extends Model
{
    use HasFactory;

    protected $table = 'cnaes';

    protected $fillable = [
        'codigo',
        'descricao',
    ];

    public function estabelecimentos(): BelongsToMany
    {
        return $this->belongsToMany(Estabelecimento::class, 'estabelecimento_cnaes')
            ->withTimestamps();
    }
}

