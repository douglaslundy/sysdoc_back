<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exame extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    use HasFactory;

    protected $fillable = ['secretaria_id', 'nome', 'codigo', 'categoria_exame_id', 'descricao', 'ativo'];

    public function categoriaExame()
    {
        return $this->hasOne(CategoriaExame::class, 'id', 'categoria_exame_id');
    }

    public function campos()
    {
        return $this->hasMany(ExameCampo::class, 'exame_id', 'id');
    }

    public function camposAtivos()
    {
        return $this->hasMany(ExameCampo::class, 'exame_id', 'id')->where('ativo', true)->orderBy('ordem');
    }

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }
}
