<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PainelPublicoConfig extends Model
{
    protected $table = 'painel_publico_config';

    protected $fillable = ['pagina_url', 'esquerda_url', 'direita_url', 'largura_esquerda'];

    protected $casts = ['largura_esquerda' => 'integer'];

    public static function atual(): self
    {
        return self::query()->firstOrCreate([]);
    }
}
