<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class LabConfig extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    protected $fillable = [
        'secretaria_id',
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

    /** Config da secretaria ativa (ou da informada: admin em "Todas", laudo/e-mail do pedido). */
    public static function get(?int $secretariaId = null): self
    {
        $defaults = ['email_habilitado' => false];

        if (Schema::hasColumn('lab_configs', 'imprimir_rascunho_exame')) {
            $defaults['imprimir_rascunho_exame'] = false;
        }

        return static::withoutSecretaria()->firstOrCreate(['secretaria_id' => $secretariaId ?? \App\Support\EscopoAlmoxarifado::id()], $defaults);
    }
}
