<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultadoCampo extends Model
{
    use HasFactory;

    protected $fillable = [
        'resultado_exame_id', 'exame_campo_id', 'exame_id',
        'valor_numerico', 'valor_texto', 'status_referencia', 'observacao',
    ];

    public function resultado()
    {
        return $this->hasOne(ResultadoExame::class, 'id', 'resultado_exame_id');
    }

    public function campo()
    {
        return $this->hasOne(ExameCampo::class, 'id', 'exame_campo_id');
    }
}
