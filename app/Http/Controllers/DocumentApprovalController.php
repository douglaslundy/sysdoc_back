<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\User;
use App\Services\Authorization\PagePermissionService;
use App\Services\DocumentDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->canViewApprovals($request->user())) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        $query = DocumentApproval::query()
            ->with(['document:id,titulo,sigilo,status', 'requester:id,name', 'approver:id,name'])
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($request->filled('document_id')) {
            $query->where('document_id', $request->integer('document_id'));
        }

        $paginator = $query->paginate(max(1, min(100, (int) $request->input('per_page', 15))));
        $currentUserId = (int) ($request->user()?->id ?? 0);

        $paginator->getCollection()->transform(fn (DocumentApproval $approval) => $this->serializeApproval($approval, $currentUserId));

        return response()->json($paginator);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $approval = DocumentApproval::query()->with(['document' => fn ($query) => $query->withTrashed()->with('versions')])->find($id);
        if (! $approval) {
            return response()->json(['message' => 'Solicitacao de aprovacao nao encontrada.'], 404);
        }

        $user = $request->user();
        $this->assertCanAct($approval, $user);

        $signedIds = array_map('intval', (array) $approval->signed_user_ids);
        $userId = (int) $user->id;

        if (in_array($userId, $signedIds, true)) {
            throw ValidationException::withMessages([
                'approval' => 'Este usuario ja assinou esta solicitacao.',
            ]);
        }

        $signedIds[] = $userId;
        $signedIds = array_values(array_unique($signedIds));
        sort($signedIds);

        $payload = [
            'signed_user_ids' => $signedIds,
        ];

        $totalRequired = max(1, (int) $approval->signer_count);
        if (count($signedIds) >= $totalRequired) {
            DB::transaction(function () use ($approval, $userId, $signedIds) {
                $document = $approval->document;

                if ($document instanceof Document && ! $document->trashed()) {
                    app(DocumentDeletionService::class)->delete($document, (int) $approval->requested_by);
                }

                $approval->update([
                    'signed_user_ids' => $signedIds,
                    'status' => 'approved',
                    'approved_by' => $userId,
                    'approved_at' => now(),
                    'rejected_by' => null,
                    'rejected_at' => null,
                ]);
            });

            return response()->json([
                'message' => 'Exclusao aprovada e documento removido com sucesso.',
            ]);
        }

        $approval->update($payload);

        return response()->json([
            'message' => sprintf('Assinatura registrada. Faltam %d aprovacao(oes).', $totalRequired - count($signedIds)),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $approval = DocumentApproval::query()->find($id);
        if (! $approval) {
            return response()->json(['message' => 'Solicitacao de aprovacao nao encontrada.'], 404);
        }

        $this->assertCanAct($approval, $request->user());

        $approval->update([
            'status' => 'rejected',
            'rejected_by' => $request->user()?->id,
            'rejected_at' => now(),
        ]);

        return response()->json([
            'message' => 'Solicitacao de exclusao rejeitada.',
        ]);
    }

    private function assertCanAct(DocumentApproval $approval, ?User $user): void
    {
        if (! $user) {
            abort(403, 'Você não possui permissão para executar esta ação.');
        }

        $signerIds = array_map('intval', (array) $approval->signer_user_ids);
        $signedIds = array_map('intval', (array) $approval->signed_user_ids);
        $userId = (int) $user->id;

        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages([
                'approval' => 'Esta solicitacao nao esta mais pendente.',
            ]);
        }

        if (! in_array($userId, $signerIds, true)) {
            abort(403, 'Você não possui permissão para executar esta ação.');
        }

        if ($userId === (int) $approval->requested_by) {
            throw ValidationException::withMessages([
                'approval' => 'O solicitante da exclusao nao pode assinar a propria solicitacao.',
            ]);
        }

        if (in_array($userId, $signedIds, true)) {
            throw ValidationException::withMessages([
                'approval' => 'Este usuario ja assinou esta solicitacao.',
            ]);
        }
    }

    private function serializeApproval(DocumentApproval $approval, int $currentUserId): array
    {
        $signerIds = array_map('intval', (array) $approval->signer_user_ids);
        $signedIds = array_map('intval', (array) $approval->signed_user_ids);
        $signers = User::query()
            ->whereIn('id', $signerIds)
            ->get(['id', 'name'])
            ->sortBy(fn (User $user) => array_search((int) $user->id, $signerIds, true))
            ->values()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'signed' => in_array((int) $user->id, $signedIds, true),
            ])
            ->all();

        $canApprove = $approval->status === 'pending'
            && in_array($currentUserId, $signerIds, true)
            && ! in_array($currentUserId, $signedIds, true)
            && $currentUserId !== (int) $approval->requested_by;

        return [
            'id' => $approval->id,
            'document_id' => $approval->document_id,
            'document' => $approval->document,
            'action' => $approval->action,
            'status' => $approval->status,
            'requester' => $approval->requester,
            'approver' => $approval->approver,
            'requested_by' => $approval->requested_by,
            'approved_by' => $approval->approved_by,
            'signer_user_ids' => $signerIds,
            'signed_user_ids' => $signedIds,
            'signer_count' => (int) $approval->signer_count,
            'signers' => $signers,
            'can_current_user_approve' => $canApprove,
            'can_current_user_reject' => $canApprove,
            'approved_at' => optional($approval->approved_at)?->toIso8601String(),
            'rejected_at' => optional($approval->rejected_at)?->toIso8601String(),
            'created_at' => optional($approval->created_at)?->toIso8601String(),
            'updated_at' => optional($approval->updated_at)?->toIso8601String(),
        ];
    }

    private function canViewApprovals(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ((string) ($user->profile ?? '') === 'admin'
            || app(PagePermissionService::class)->canAccess($user, '/documentos/aprovacoes')) {
            return true;
        }

        return DocumentApproval::query()
            ->where('status', 'pending')
            ->whereJsonContains('signer_user_ids', (int) $user->id)
            ->exists();
    }
}
