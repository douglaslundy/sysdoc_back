<?php

namespace App\Http\Controllers;

use App\Http\Requests\AutorizarCadastroRequest;
use App\Http\Requests\RecusarCadastroRequest;
use App\Models\CadastroSolicitacao;
use App\Models\Scopes\CadastroProtocoloScope;
use App\Services\Cadastro\CadastroSolicitacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Só admin e gestor da secretaria do pedido; qualquer outro recebe 404 (não revela que o pedido existe). */
class CadastroSolicitacaoController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $s = $this->pedido($request, $id)->load(['secretaria:id,nome', 'decididoPor:id,name']);

        // password_hash nunca sai; o CPF vai inteiro porque quem decide precisa conferir a identidade.
        return response()->json([
            'id' => $s->id, 'nome' => $s->nome, 'preferred_name' => $s->preferred_name, 'email' => $s->email,
            'cpf' => $s->cpf, 'phone' => $s->phone, 'cargo' => $s->cargo, 'observacao' => $s->observacao,
            'secretaria' => $s->secretaria ? ['id' => $s->secretaria->id, 'nome' => $s->secretaria->nome] : null,
            'status' => $s->status, 'created_at' => $s->created_at, 'protocol_id' => $s->protocol_id,
            'motivo_recusa' => $s->motivo_recusa,
            'decidido_por' => $s->decididoPor ? ['id' => $s->decididoPor->id, 'name' => $s->decididoPor->name] : null,
            'decidido_em' => $s->decidido_em,
        ]);
    }

    public function autorizar(AutorizarCadastroRequest $request, int $id, CadastroSolicitacaoService $service): JsonResponse
    {
        $user = $service->aprovar(
            $this->pedido($request, $id),
            $request->user(),
            $request->validated('perfil_slug'),
            $request->validated('secretaria_ids'),
        );

        return response()->json(['status' => 'aprovada', 'user_id' => $user->id]);
    }

    public function recusar(RecusarCadastroRequest $request, int $id, CadastroSolicitacaoService $service): JsonResponse
    {
        $service->recusar($this->pedido($request, $id), $request->user(), $request->validated('motivo'));

        return response()->json(['status' => 'recusada']);
    }

    private function pedido(Request $request, int $id): CadastroSolicitacao
    {
        $s = CadastroSolicitacao::find($id);
        abort_unless($s && CadastroProtocoloScope::pode($request->user(), $s->secretaria_id), 404, 'Pedido não encontrado.');

        return $s;
    }
}
