<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Não usa BelongsToSecretaria: o pedido é público e anterior a qualquer contexto de secretaria.
class CadastroSolicitacao extends Model
{
    protected $table = 'cadastro_solicitacoes';

    // status/protocol_id/user_id/decidido_* ficam fora de propósito: só o fluxo de aprovação os define.
    protected $fillable = [
        'nome', 'preferred_name', 'email', 'cpf', 'phone', 'password_hash', 'secretaria_id', 'cargo', 'observacao',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = ['decidido_em' => 'datetime'];

    // Mesmo contrato de User::scopeWhereCpfIgual (a coluna já guarda só dígitos; o REPLACE cobre linhas antigas).
    public function scopeWhereCpfIgual($query, string $cpf)
    {
        return $query->whereRaw(
            "REPLACE(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', ''), '/', '') = ?",
            [\App\Support\Cpf::digits($cpf)]
        );
    }
}
