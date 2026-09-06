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
    ];

    protected $casts = [
        'data_visita' => 'date',
    ];

    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class, 'estabelecimento_id');
    }

    public function fiscal()
    {
        return $this->belongsTo(User::class, 'fiscal_id');
    }

    public function attachments()
    {
        return $this->hasMany(FiscalizacaoAttachment::class, 'fiscalizacao_id');
    }
}
