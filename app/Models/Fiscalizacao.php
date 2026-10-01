<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fiscalizacao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fiscalizacoes';

    protected $fillable = [
        'estabelecimento_id',
        'fiscal_id',
        'data_visita',
        'resultado',
        'observacoes',
        'protocolo',
        'origem',
        'motivo_id',
        'assunto',
        'descricao_denuncia',
        'local_endereco',
        'estabelecimento_nome_informado',
        'denunciante_nome',
        'denunciante_contato',
    ];

    // O hash da senha de consulta da denúncia nunca sai em JSON/array.
    protected $hidden = ['senha_consulta_hash'];

    protected $casts = [
        'data_visita' => 'date',
    ];

    public function motivo()
    {
        return $this->belongsTo(PeticaoMotivo::class, 'motivo_id');
    }

    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class, 'estabelecimento_id');
    }

    public function fiscal()
    {
        return $this->belongsTo(User::class, 'fiscal_id');
    }

    public function movimentacoes()
    {
        return $this->hasMany(FiscalizacaoMovimentacao::class, 'fiscalizacao_id')->orderBy('id');
    }

    public function attachments()
    {
        return $this->hasMany(FiscalizacaoAttachment::class, 'fiscalizacao_id');
    }
}
