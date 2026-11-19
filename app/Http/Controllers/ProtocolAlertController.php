<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProtocolAlertRequest;
use App\Http\Requests\StoreProtocolAlertRequest;
use App\Models\ProtocolAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProtocolAlertController extends Controller
{
    public const CHANNELS = ['whatsapp', 'email'];
    public const RECIPIENTS = ['administrador', 'gestor', 'usuario', 'tfd', 'motorista', 'todos', 'assinantes_exclusao_documento', 'solicitante_documento', 'criador_documento', 'solicitante_almoxarifado', 'responsavel_almoxarifado', 'aprovadores_almoxarifado', 'entregadores_almoxarifado', 'criador_kanban', 'responsavel_kanban', 'criador_oficio', 'destinatario_protocolo_oficio', 'remetente_chat', 'destinatario_chat', 'participantes_chat'];
    public const CONDITIONS = ['novo', 'em_andamento', 'aguardando_resposta', 'vencendo', 'vencido', 'concluido'];

    public function index(): JsonResponse
    {
        return response()->json(ProtocolAlert::orderBy('nome')->get()->map(fn (ProtocolAlert $a) => $this->comCatalogo($a))->values());
    }

    /** Linhas `sistema` levam as variáveis aceitas e o texto padrão (para "Restaurar padrão"). */
    private function comCatalogo(ProtocolAlert $alert): array
    {
        $dados = $alert->toArray();
        $padrao = $alert->sistema ? config("alert_catalog.sistema.{$alert->modulo}:{$alert->gatilho}") : null;
        $dados['variaveis'] = $padrao['variaveis'] ?? [];
        $dados['padrao_template'] = $padrao['template'] ?? null;
        $dados['padrao_assunto'] = $padrao['assunto'] ?? null;

        return $dados;
    }

    /** Volta texto e assunto de uma mensagem do sistema ao padrão do catálogo. */
    public function restaurar(int $id): JsonResponse
    {
        $alert = ProtocolAlert::where('sistema', true)->find($id);
        $padrao = $alert ? config("alert_catalog.sistema.{$alert->modulo}:{$alert->gatilho}") : null;
        if (! $alert || ! $padrao) {
            return response()->json(['message' => 'Mensagem do sistema não encontrada.'], 404);
        }

        $alert->update(['template' => $padrao['template'], 'assunto' => $padrao['assunto']]);

        return response()->json($this->comCatalogo($alert->fresh()));
    }

    public function store(StoreProtocolAlertRequest $request): JsonResponse
    {
                $validated = $request->validated();

        return response()->json(
            ProtocolAlert::create([
                ...$validated,
                'ativo' => $validated['ativo'] ?? true,
                'prevenir_duplicidade' => $validated['prevenir_duplicidade'] ?? true,
            ]),
            201
        );
    }

    public function update(UpdateProtocolAlertRequest $request, int $id): JsonResponse
    {
        $alert = ProtocolAlert::find($id);
        if (! $alert) {
            return response()->json(['message' => 'Alerta não encontrado.'], 404);
        }

                $validated = $request->validated();

        // Mensagem do sistema: módulo, gatilho, canais e destinatários são definidos pelo código; só o texto muda.
        if ($alert->sistema) {
            $validated = array_intersect_key($validated, array_flip(['nome', 'descricao', 'template', 'assunto', 'ativo']));
            if (array_key_exists('template', $validated) && trim((string) $validated['template']) === '') {
                return response()->json(['message' => 'O texto da mensagem não pode ficar vazio.'], 422);
            }
        }

        $alert->update($validated);

        return response()->json($this->comCatalogo($alert->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
        $alert = ProtocolAlert::find($id);
        if (! $alert) {
            return response()->json(['message' => 'Alerta não encontrado.'], 404);
        }

        $alert->update(['ativo' => false]);
        return response()->json(['message' => 'Alerta inativado com sucesso.']);
    }
}
