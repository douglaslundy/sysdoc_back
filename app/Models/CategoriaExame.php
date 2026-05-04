<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaExame extends Model
{
    use HasFactory;

    protected $table = 'categoria_exames';

    protected $fillable = ['nome', 'ativo'];

    public function exames()
    {
        return $this->hasMany(Exame::class, 'categoria_exame_id', 'id');
    }
}
