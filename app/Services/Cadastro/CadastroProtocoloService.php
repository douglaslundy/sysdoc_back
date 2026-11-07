<?php

namespace App\Services\Cadastro;

use App\Models\CadastroSolicitacao;
use App\Models\KanbanTask;
use App\Models\Protocol;
use App\Models\Scopes\CadastroProtocoloScope;

/**
 * Abre o protocolo e o card do Kanban de um pedido de cadastro. Nunca grava a senha (nem o hash).
 * Sem unidades de origem/destino (as colunas são anuláveis): assim nenhum membro de unidade o enxerga;
 * a visibilidade é decidida por CadastroProtocoloScope (admin e gestor da secretaria do pedido).
 */
class CadastroProtocoloService
{
    /** Idempotente: se o pedido já tem protocolo, devolve o existente. Chame dentro de uma transação. */
    public function abrir(CadastroSolicitacao $s): Protocol
    {
        if ($s->protocol_id) {
            return Protocol::withoutGlobalScope(CadastroProtocoloScope::class)->findOrFail($s->protocol_id);
        }

        $assunto = 'Solicitação de cadastro: '.$s->nome;

        $protocol = Protocol::create([
            'numero' => Protocol::gerarNumero(),
            'assunto' => mb_substr($assunto, 0, 200),
            'descricao' => $this->descricao($s),
            'tipo' => CadastroProtocoloScope::TIPO,
            'status' => 'novo',
            'prioridade' => 'normal',
            'solicitante_tipo' => 'externo',
            'solicitante_nome' => mb_substr((string) $s->nome, 0, 150),
            'criado_por_id' => null,
            'secretaria_id' => $s->secretaria_id,
        ]);

        KanbanTask::create([
            'protocol_id' => $protocol->id,
            'titulo' => $protocol->assunto,
            'descricao' => $protocol->descricao,
            'status' => 'novo',
            'prioridade' => 'normal',
            'visibility' => 'private',
            'created_by_id' => null,
            'secretaria_id' => $s->secretaria_id,
        ]);

        // protocol_id fica fora do fillable do pedido: só este serviço o define.
        $s->forceFill(['protocol_id' => $protocol->id])->save();

        return $protocol;
    }

    private function descricao(CadastroSolicitacao $s): string
    {
        $cpf = str_pad(substr(preg_replace('/\D/', '', (string) $s->cpf), -2), 2, '*', STR_PAD_LEFT);

        return implode("\n", array_filter([
            "Nome: {$s->nome}",
            "Cargo: {$s->cargo}",
            "CPF: ***.***.***-{$cpf}",
            "E-mail: {$s->email}",
            $s->observacao ? "Observação: {$s->observacao}" : null,
            "Pedido de cadastro #{$s->id}: aprovar ou recusar em Usuários > Pedidos de cadastro.",
        ]));
    }
}
