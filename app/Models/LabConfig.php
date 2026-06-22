<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class LabConfig extends Model
{
    protected $fillable = [
        'email_habilitado',
        'nome_estabelecimento',
        'razao_social',
        'endereco_rua',
        'endereco_numero',
        'endereco_bairro',
        'endereco_cep',
        'telefone',
        'cnpj',
        'email_lab',
        'rodape1',
        'rodape2',
        'imprimir_rascunho_exame',
    ];

    protected $casts = [
        'email_habilitado' => 'boolean',
        'imprimir_rascunho_exame' => 'boolean',
    ];

    // Retorna a única config existente (singleton)
    public static function get(): self
    {
        $defaults = ['email_habilitado' => false];

        if (Schema::hasColumn('lab_configs', 'imprimir_rascunho_exame')) {
            $defaults['imprimir_rascunho_exame'] = false;
        }

        return static::firstOrCreate([], $defaults);
    }
}
