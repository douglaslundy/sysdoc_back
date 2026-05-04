<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampoReferencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'exame_campo_id', 'perfil', 'sexo', 'idade_min_dias', 'idade_max_dias',
        'valor_min', 'valor_max', 'valor_texto', 'descricao',
    ];

    public const PERFIS = [
        'geral', 'adulto_m', 'adulto_f',
        'crianca', 'crianca_m', 'crianca_f',
        'adolescente', 'adolescente_m', 'adolescente_f',
        'idoso', 'idoso_m', 'idoso_f',
        'gestante', 'gestante_t1', 'gestante_t2', 'gestante_t3',
        'recem_nascido',
    ];

    public const PERFIS_LABELS = [
        'geral'          => 'Geral',
        'adulto_m'       => 'Adulto Masculino',
        'adulto_f'       => 'Adulto Feminino',
        'crianca'        => 'Criança (2–11 anos)',
        'crianca_m'      => 'Criança Masculino',
        'crianca_f'      => 'Criança Feminino',
        'adolescente'    => 'Adolescente (12–17 anos)',
        'adolescente_m'  => 'Adolescente Masculino',
        'adolescente_f'  => 'Adolescente Feminino',
        'idoso'          => 'Idoso (≥ 60 anos)',
        'idoso_m'        => 'Idoso Masculino',
        'idoso_f'        => 'Idoso Feminino',
        'gestante'       => 'Gestante',
        'gestante_t1'    => 'Gestante 1º Trimestre',
        'gestante_t2'    => 'Gestante 2º Trimestre',
        'gestante_t3'    => 'Gestante 3º Trimestre',
        'recem_nascido'  => 'Recém-nascido (0–28 dias)',
    ];

    public function campo()
    {
        return $this->hasOne(ExameCampo::class, 'id', 'exame_campo_id');
    }
}
