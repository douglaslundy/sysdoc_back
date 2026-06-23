<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoConfig extends Model
{
    protected $table = 'almoxarifado_configs';

    protected $fillable = [
        'permitir_saida_sem_saldo',
        'permitir_transferencia_entre_secretarias',
        'exigir_justificativa_saida',
        'exigir_localizacao_produto',
        'notificar_estoque_minimo',
        'estoque_minimo_alerta_percentual',
        'permite_produto_sem_validade',
        'observacoes',
    ];

    protected $casts = [
        'permitir_saida_sem_saldo' => 'boolean',
        'permitir_transferencia_entre_secretarias' => 'boolean',
        'exigir_justificativa_saida' => 'boolean',
        'exigir_localizacao_produto' => 'boolean',
        'notificar_estoque_minimo' => 'boolean',
        'estoque_minimo_alerta_percentual' => 'integer',
        'permite_produto_sem_validade' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'permitir_saida_sem_saldo' => false,
            'permitir_transferencia_entre_secretarias' => true,
            'exigir_justificativa_saida' => true,
            'exigir_localizacao_produto' => false,
            'notificar_estoque_minimo' => true,
            'estoque_minimo_alerta_percentual' => 20,
            'permite_produto_sem_validade' => true,
        ]);
    }
}
