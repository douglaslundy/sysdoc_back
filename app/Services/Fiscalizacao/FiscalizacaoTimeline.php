<?php

namespace App\Services\Fiscalizacao;

use App\Models\Fiscalizacao;
use App\Models\FiscalizacaoMovimentacao;

/**
 * Registra o histórico de movimentação de uma fiscalização.
 * `publico = true` é o que o denunciante consegue ver na consulta pública.
 */
class FiscalizacaoTimeline
{
    public function registrar(
        Fiscalizacao $fiscalizacao,
        string $acao,
        ?string $descricao = null,
        bool $publico = false,
        ?int $userId = null,
        ?array $dados = null
    ): FiscalizacaoMovimentacao {
        return $fiscalizacao->movimentacoes()->create([
            'user_id' => $userId,
            'acao' => $acao,
            'descricao' => $descricao,
            'publico' => $publico,
            'dados' => $dados,
        ]);
    }
}
