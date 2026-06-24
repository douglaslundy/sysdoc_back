<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'ordem' => 'integer',
        'ativo' => 'boolean',
    ];
}
