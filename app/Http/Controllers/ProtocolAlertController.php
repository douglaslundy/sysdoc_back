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
        return response()->json(ProtocolAlert::orderBy('nome')->get());
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

        $alert->update($validated);

        return response()->json($alert->fresh());
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
