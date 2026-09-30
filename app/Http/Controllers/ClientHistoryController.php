<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Client;
use App\Services\Audit\AuditDescriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientHistoryController extends Controller
{
    private const PER_PAGE = 30;

    /**
     * Historico do cidadao: tudo o que foi feito com/sobre ele, do mais recente para o mais antigo.
     * Consulta indexada por (client_id, id) e paginada.
     */
    public function index(Request $request, int $client): JsonResponse
    {
        if (! $request->user()?->canViewClientHistory()) {
            return response()->json(['message' => 'Você não possui permissão para ver o histórico do cidadão.'], 403);
        }

        if (! Client::whereKey($client)->exists()) {
            return response()->json(['message' => 'Cidadão não encontrado.'], 404);
        }

        $page = AuditLog::query()
            ->where('client_id', $client)
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        $page->getCollection()->transform(function (AuditLog $log) {
            $description = AuditDescriber::describe($log);

            return [
                'id' => $log->id,
                'titulo' => $description['titulo'],
                'detalhe' => $description['detalhe'],
                'usuario' => $log->user_name,
                'acao' => $log->action,
                'data' => $log->created_at?->toISOString(),
            ];
        });

        return response()->json($page);
    }
}
