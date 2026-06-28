<?php

namespace App\Http\Controllers;

use App\Models\ProtocolAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProtocolAlertController extends Controller
{
    private const CHANNELS = ['whatsapp', 'email'];
    private const RECIPIENTS = ['administrador', 'gestor', 'usuario', 'tfd', 'motorista', 'todos', 'assinantes_exclusao_documento', 'solicitante_documento', 'criador_documento', 'solicitante_almoxarifado', 'responsavel_almoxarifado', 'aprovadores_almoxarifado', 'entregadores_almoxarifado', 'criador_kanban', 'responsavel_kanban', 'criador_oficio', 'destinatario_protocolo_oficio', 'remetente_chat', 'destinatario_chat', 'participantes_chat'];
    private const CONDITIONS = ['novo', 'em_andamento', 'aguardando_resposta', 'vencendo', 'vencido', 'concluido'];

    public function index(): JsonResponse
    {
        return response()->json(ProtocolAlert::orderBy('nome')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'modulo' => 'required|string|max:80',
            'gatilho' => 'required|string|max:80',
            'condicoes' => 'nullable|array',
            'condicoes.*' => ['string', Rule::in(self::CONDITIONS)],
            'canais' => 'required|array|min:1',
            'canais.*' => ['string', Rule::in(self::CHANNELS)],
            'destinatarios' => 'nullable|array',
            'destinatarios.*' => ['string', Rule::in(self::RECIPIENTS)],
            'template' => 'nullable|string',
            'ativo' => 'nullable|boolean',
            'frequencia' => 'nullable|string|max:60',
            'prevenir_duplicidade' => 'nullable|boolean',
        ]);

        return response()->json(
            ProtocolAlert::create([
                ...$validated,
                'ativo' => $validated['ativo'] ?? true,
                'prevenir_duplicidade' => $validated['prevenir_duplicidade'] ?? true,
            ]),
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $alert = ProtocolAlert::find($id);
        if (! $alert) {
            return response()->json(['message' => 'Alerta não encontrado.'], 404);
        }

        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:150',
            'descricao' => 'nullable|string',
            'modulo' => 'sometimes|required|string|max:80',
            'gatilho' => 'sometimes|required|string|max:80',
            'condicoes' => 'nullable|array',
            'condicoes.*' => ['string', Rule::in(self::CONDITIONS)],
            'canais' => 'required|array|min:1',
            'canais.*' => ['string', Rule::in(self::CHANNELS)],
            'destinatarios' => 'nullable|array',
            'destinatarios.*' => ['string', Rule::in(self::RECIPIENTS)],
            'template' => 'nullable|string',
            'ativo' => 'nullable|boolean',
            'frequencia' => 'nullable|string|max:60',
            'prevenir_duplicidade' => 'nullable|boolean',
        ]);

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
