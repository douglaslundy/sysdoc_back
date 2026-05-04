<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExameCampo extends Model
{
    use HasFactory;

    protected $fillable = [
        'exame_id', 'nome', 'descricao', 'tipo_valor', 'unidade',
        'opcoes_selecao', 'ordem', 'obrigatorio', 'ativo',
    ];

    protected $casts = [
        'opcoes_selecao' => 'array',
    ];

    public function exame()
    {
        return $this->hasOne(Exame::class, 'id', 'exame_id');
    }

    public function referencias()
    {
        return $this->hasMany(CampoReferencia::class, 'exame_campo_id', 'id');
    }

    public function referenciaParaPerfil(string $perfil): ?CampoReferencia
    {
        return $this->referencias()
            ->where('perfil', $perfil)
            ->orWhere('perfil', 'geral')
            ->orderByRaw("FIELD(perfil, ?, 'geral')", [$perfil])
            ->first();
    }
}
