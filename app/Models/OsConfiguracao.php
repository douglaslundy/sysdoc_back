<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OsConfiguracao extends Model
{
    protected $table = 'os_configuracoes';

    protected $fillable = ['secretaria_id', 'solicitante_aponta_tecnico'];

    protected $casts = ['solicitante_aponta_tecnico' => 'boolean'];

    /** Regra da secretaria; sem ela vale o padrão global (secretaria_id nulo); sem nenhum, desligado. */
    public static function solicitanteApontaTecnico(int $secretariaId): bool
    {
        $linha = static::where('secretaria_id', $secretariaId)->first() ?? static::whereNull('secretaria_id')->first();

        return (bool) $linha?->solicitante_aponta_tecnico;
    }
}
