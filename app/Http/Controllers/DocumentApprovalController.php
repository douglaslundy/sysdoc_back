<?php

namespace App\Http\Controllers;

use App\Models\DocumentApproval;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->canViewApprovals($request->user())) {
            return response()->json(['message' => 'Voce nao possui permissao para executar esta acao.'], 403);
        }

        $query = DocumentApproval::query()
            ->with(['document:id,titulo,sigilo,status', 'requester:id,name', 'approver:id,name'])
            ->orderByDesc('approved_at')
            ->orderByDesc('id');

        if ($request->filled('document_id')) {
            $query->where('document_id', $request->integer('document_id'));
        }

        return response()->json(
            $query->paginate(max(1, min(100, (int) $request->input('per_page', 15))))
        );
    }

    private function canViewApprovals(?\App\Models\User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (string) ($user->profile ?? '') === 'admin'
            || app(PagePermissionService::class)->canAccess($user, '/documentos/aprovacoes');
    }
}
