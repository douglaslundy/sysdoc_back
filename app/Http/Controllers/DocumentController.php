<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentConfig;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Authorization\PagePermissionService;
use App\Services\DocumentDeletionService;
use App\Services\SystemAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    private const DISK = 'private';

    private const SIGILOS = ['publico', 'interno', 'restrito'];

    public function index(Request $request): JsonResponse
    {
        if (! $this->canViewModule($request->user())) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        $query = Document::query()
            ->select([
                'id',
                'document_type_id',
                'titulo',
                'sigilo',
                'status',
                'current_version_number',
                'created_by',
                'updated_at',
            ])
            ->with(['type:id,codigo,nome', 'creator:id,name'])
            ->when(! $this->isAdmin($request->user()), function ($builder) use ($request) {
                $visibleUnitIds = $this->visibleDocumentUnitIds($request->user());

                $builder->where(function ($q) use ($request, $visibleUnitIds) {
                    $q->where('sigilo', 'publico')
                        ->orWhere('created_by', $request->user()?->id)
                        ->orWhere(function ($internalQuery) use ($visibleUnitIds) {
                            $internalQuery
                                ->where('sigilo', 'interno')
                                ->whereHas('creator.protocolUnits', function ($protocolUnitQuery) use ($visibleUnitIds) {
                                    $protocolUnitQuery
                                        ->where('ativo', true)
                                        ->whereHas('unit', function ($unitQuery) use ($visibleUnitIds) {
                                            $unitQuery
                                                ->whereIn('id', $visibleUnitIds)
                                                ->orWhereIn('parent_id', $visibleUnitIds);
                                        });
                                });
                        });
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', "%{$search}%")
                    ->orWhere('resumo', 'like', "%{$search}%");
            });
        }

        foreach (['document_type_id', 'sigilo', 'status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        return response()->json(
            $query->paginate(max(1, min(100, (int) $request->input('per_page', 15))))
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $document = Document::with([
            'type:id,codigo,nome,descricao',
            'creator:id,name',
            'updater:id,name',
            'deleter:id,name',
            'versions.uploader:id,name',
            'latestVersion.uploader:id,name',
        ])->find($id);

        if (! $document || ! $this->canReadDocument($document, $request->user())) {
            return response()->json(['message' => 'Documento não encontrado.'], 404);
        }

        AuditService::record('VIEW', $document, null, [
            'document_version_id' => $document->latest_version_id,
            'current_version_number' => $document->current_version_number,
        ]);

        return response()->json($document);
    }

    public function history(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);
        if (! $document || ! $this->canReadDocument($document, $request->user())) {
            return response()->json(['message' => 'Documento nao encontrado.'], 404);
        }

        $history = AuditLog::query()
            ->where('model_type', 'Document')
            ->where('model_id', $document->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get([
                'id',
                'action',
                'user_id',
                'user_name',
                'old_values',
                'new_values',
                'created_at',
                'endpoint',
                'method',
                'ip_address',
            ]);

        return response()->json($history);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->canManageDocuments($request->user())) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        $validated = $request->validate([
            'document_type_id' => ['nullable', 'integer', 'exists:document_types,id'],
            'titulo' => ['required', 'string', 'max:200'],
            'resumo' => ['nullable', 'string'],
            'sigilo' => ['required', 'string', Rule::in(self::SIGILOS)],
            'status' => ['nullable', 'string', 'max:30'],
            'arquivo' => ['required', 'file', 'max:51200'],
        ]);

        $document = DB::transaction(function () use ($request, $validated) {
            $document = Document::create([
                'document_type_id' => $validated['document_type_id'] ?? null,
                'titulo' => $validated['titulo'],
                'resumo' => $validated['resumo'] ?? null,
                'sigilo' => $validated['sigilo'],
                'status' => $validated['status'] ?? 'rascunho',
                'current_version_number' => 0,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $this->storeVersion($document, $validated['arquivo'], $request->user());

            AuditService::record('CREATE', $document, null, $document->fresh()->toArray());

            return $document->fresh(['type:id,codigo,nome', 'creator:id,name', 'latestVersion.uploader:id,name']);
        });

        app(SystemAlertService::class)->dispatch('documentos', 'documento_cadastrado', [
            'document' => $document->loadMissing('creator:id,name,email,phone'),
            'requester' => $request->user(),
        ]);

        return response()->json($document, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);
        if (! $document || ! $this->canManageDocumentRecord($document, $request->user())) {
            return response()->json(['message' => 'Documento não encontrado.'], 404);
        }

        $validated = $request->validate([
            'document_type_id' => ['nullable', 'integer', 'exists:document_types,id'],
            'titulo' => ['sometimes', 'required', 'string', 'max:200'],
            'resumo' => ['nullable', 'string'],
            'sigilo' => ['sometimes', 'required', 'string', Rule::in(self::SIGILOS)],
            'status' => ['nullable', 'string', 'max:30'],
            'arquivo' => ['nullable', 'file', 'max:51200'],
        ]);

        $old = $document->toArray();

        DB::transaction(function () use ($request, $document, $validated) {
            $document->update([
                'document_type_id' => array_key_exists('document_type_id', $validated) ? $validated['document_type_id'] : $document->document_type_id,
                'titulo' => $validated['titulo'] ?? $document->titulo,
                'resumo' => array_key_exists('resumo', $validated) ? $validated['resumo'] : $document->resumo,
                'sigilo' => $validated['sigilo'] ?? $document->sigilo,
                'status' => $validated['status'] ?? $document->status,
                'updated_by' => $request->user()?->id,
            ]);

            if ($request->hasFile('arquivo')) {
                $this->storeVersion($document->fresh(), $request->file('arquivo'), $request->user());
            }
        });

        AuditService::record('UPDATE', $document, $old, $document->fresh()->toArray());

        $freshDocument = $document->fresh(['creator:id,name,email,phone']);
        app(SystemAlertService::class)->dispatch('documentos', 'documento_atualizado', [
            'document' => $freshDocument,
            'requester' => $request->user(),
        ]);

        if ((string) ($freshDocument->status ?? '') === 'publicado') {
            app(SystemAlertService::class)->dispatch('documentos', 'documento_publicado', [
                'document' => $freshDocument,
                'requester' => $request->user(),
            ]);
        }

        return response()->json($document->fresh(['type:id,codigo,nome', 'creator:id,name', 'latestVersion.uploader:id,name']));
    }

    public function uploadVersion(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);
        if (! $document || ! $this->canManageDocumentRecord($document, $request->user())) {
            return response()->json(['message' => 'Documento não encontrado.'], 404);
        }

        $validated = $request->validate([
            'arquivo' => ['required', 'file', 'max:51200'],
        ]);

        $version = DB::transaction(function () use ($document, $validated, $request) {
            return $this->storeVersion($document, $validated['arquivo'], $request->user());
        });

        AuditService::record('VERSION_CREATE', $document, null, [
            'document_version_id' => $version->id,
            'original_name' => $version->original_name,
            'version_number' => $version->version_number,
        ]);

        return response()->json($document->fresh(['type:id,codigo,nome', 'creator:id,name', 'latestVersion.uploader:id,name']));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $document = Document::with('versions')->find($id);
        if (! $document || ! $this->canManageDocumentRecord($document, $request->user())) {
            return response()->json(['message' => 'Documento não encontrado.'], 404);
        }

        try {
            $signers = [];
            if ($this->currentConfig()->requiresTripleSignatureFor((string) $document->sigilo)) {
                $signers = $this->resolveTripleSignatureSigners($request->user()?->id);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Nao foi possivel iniciar a exclusao.',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($signers !== []) {
            if (DocumentApproval::query()
                ->where('document_id', $document->id)
                ->where('action', 'delete')
                ->where('status', 'pending')
                ->exists()) {
                return response()->json([
                    'message' => 'Ja existe uma solicitacao de exclusao pendente para este documento.',
                ], 422);
            }

            $approval = DocumentApproval::create([
                'document_id' => $document->id,
                'action' => 'delete',
                'status' => 'pending',
                'requested_by' => $request->user()?->id,
                'signer_user_ids' => $signers,
                'signed_user_ids' => [],
                'signer_count' => count($signers),
                'snapshot' => $document->toArray(),
            ]);

            $signerUsers = User::query()
                ->whereIn('id', $signers)
                ->get()
                ->sortBy(fn (User $user) => array_search((int) $user->id, $signers, true))
                ->values();

            app(SystemAlertService::class)->dispatch('documentos', 'solicitacao_exclusao_pendente', [
                'approval' => $approval,
                'document' => $document->loadMissing('creator:id,name,email,phone'),
                'requester' => $request->user(),
                'signers' => $signerUsers,
            ]);

            return response()->json([
                'message' => 'Solicitacao de exclusao enviada para os responsaveis pela tripla assinatura.',
            ], 202);
        }

        DB::transaction(function () use ($request, $document) {
            app(DocumentDeletionService::class)->delete($document, $request->user()?->id);
        });

        return response()->json(['message' => 'Documento removido com sucesso.']);
    }

    public function approvals(Request $request): JsonResponse
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

        $paginator->getCollection()->transform(function (DocumentApproval $approval) use ($currentUserId) {
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
        });

        return response()->json($paginator);
    }

    public function versions(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);
        if (! $document || ! $this->canReadDocument($document, $request->user())) {
            return response()->json(['message' => 'Documento não encontrado.'], 404);
        }

        return response()->json(
            $document->versions()->with('uploader:id,name')->orderByDesc('version_number')->get()
        );
    }

    public function downloadVersion(Request $request, int $documentId, int $versionId): StreamedResponse|JsonResponse
    {
        $document = Document::find($documentId);
        $version = DocumentVersion::find($versionId);

        if (! $document || ! $version || (int) $version->document_id !== (int) $document->id || ! $this->canReadDocument($document, $request->user())) {
            return response()->json(['message' => 'Versao nao encontrada.'], 404);
        }

        $contents = $this->decryptVersion($version);
        if ($contents === null) {
            return response()->json(['message' => 'Arquivo não encontrado no armazenamento.'], 404);
        }

        AuditService::record('DOWNLOAD', $document, null, [
            'document_version_id' => $version->id,
            'original_name' => $version->original_name,
        ]);

        return response()->streamDownload(function () use ($contents) {
            echo $contents;
        }, $this->sanitizeFilename($version->original_name), [
            'Content-Type' => $version->mime_type ?: 'application/octet-stream',
        ]);
    }

    public function destroyVersion(Request $request, int $documentId, int $versionId): JsonResponse
    {
        if (! $this->isAdmin($request->user())) {
            return response()->json(['message' => 'Você não possui permissão para excluir anexos deste documento.'], 403);
        }

        $document = Document::find($documentId);
        $version = DocumentVersion::find($versionId);

        if (! $document || ! $version || (int) $version->document_id !== (int) $document->id) {
            return response()->json(['message' => 'Anexo não encontrado.'], 404);
        }

        DB::transaction(function () use ($document, $request, $version) {
            if ($version->disk && $version->path && Storage::disk($version->disk)->exists($version->path)) {
                Storage::disk($version->disk)->delete($version->path);
            }

            $version->delete();

            $document->update([
                'current_version_number' => (int) ($document->versions()->max('version_number') ?? 0),
                'updated_by' => $request->user()?->id,
            ]);
        });

        AuditService::record('VERSION_DELETE', $document, [
            'document_version_id' => $version->id,
            'version_number' => $version->version_number,
            'original_name' => $version->original_name,
        ], [
            'current_version_number' => (int) ($document->fresh()->current_version_number ?? 0),
        ]);

        return response()->json([
            'message' => 'Anexo removido com sucesso.',
            'document' => $document->fresh(['type:id,codigo,nome', 'creator:id,name', 'latestVersion.uploader:id,name']),
        ]);
    }

    private function storeVersion(Document $document, UploadedFile $file, ?User $user): DocumentVersion
    {
        $nextVersion = ((int) $document->current_version_number) + 1;
        $contents = file_get_contents($file->getRealPath()) ?: '';
        $payload = Crypt::encryptString(base64_encode($contents));
        $path = sprintf('documents/%d/v%04d-%s.enc', $document->id, $nextVersion, (string) Str::uuid());

        Storage::disk(self::DISK)->put($path, $payload);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => $nextVersion,
            'uploaded_by' => $user?->id,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'size_bytes' => strlen($contents),
            'checksum' => hash('sha256', $contents),
            'encrypted' => true,
        ]);

        $document->update(['current_version_number' => $nextVersion, 'updated_by' => $user?->id]);

        return $version;
    }

    private function decryptVersion(DocumentVersion $version): ?string
    {
        if (! $version->disk || ! $version->path || ! Storage::disk($version->disk)->exists($version->path)) {
            return null;
        }

        $payload = Storage::disk($version->disk)->get($version->path);

        try {
            $decoded = base64_decode(Crypt::decryptString($payload), true);
            return $decoded === false ? null : $decoded;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function canReadDocument(Document $document, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $this->canViewModule($user)) {
            return false;
        }

        if ((int) $document->created_by === (int) $user->id) {
            return true;
        }

        if ($document->sigilo === 'publico') {
            return true;
        }

        if ($document->sigilo === 'interno') {
            return $this->sharesDocumentUnitScope((int) $document->created_by, $user);
        }

        return false;
    }

    private function canManageDocumentRecord(Document $document, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $this->canManageDocuments($user)) {
            return false;
        }

        return (int) $document->created_by === (int) $user->id;
    }

    private function isAdmin(?User $user): bool
    {
        return (string) ($user?->profile ?? '') === 'admin';
    }

    private function canViewModule(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->isAdmin($user)
            || app(PagePermissionService::class)->canAccess($user, '/documentos');
    }

    private function canManageDocuments(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->isAdmin($user)
            || app(PagePermissionService::class)->canAccess($user, '/documentos');
    }

    private function canViewApprovals(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->isAdmin($user)
            || app(PagePermissionService::class)->canAccess($user, '/documentos/aprovacoes');
    }

    private function resolveTripleSignatureSigners(?int $requesterId = null): array
    {
        $signers = $this->currentConfig()->signerUserIds();
        if (count($signers) !== 3) {
            throw ValidationException::withMessages([
                'triple_signature' => 'A tripla assinatura está ativa, mas a configuração dos 3 usuários responsáveis está incompleta.',
            ]);
        }

        $existing = User::query()->whereIn('id', $signers)->pluck('id')->all();
        if (count($existing) !== 3) {
            throw ValidationException::withMessages([
                'triple_signature' => 'A configuração da tripla assinatura possui usuários inválidos.',
            ]);
        }

        if ($requesterId && in_array((int) $requesterId, array_map('intval', $signers), true)) {
            throw ValidationException::withMessages([
                'triple_signature' => 'O solicitante da exclusao nao pode ser um dos usuarios responsaveis pela tripla assinatura.',
            ]);
        }

        return array_map('intval', $signers);
    }

    private function currentConfig(): DocumentConfig
    {
        return DocumentConfig::current();
    }

    private function sanitizeFilename(string $filename): string
    {
        $sanitized = str_replace(['\\', '/'], '-', $filename);
        $sanitized = preg_replace('/[^A-Za-z0-9._\\- ]/', '', $sanitized) ?? 'arquivo';
        $sanitized = trim($sanitized);

        return $sanitized !== '' ? $sanitized : 'arquivo';
    }

    private function sharesDocumentUnitScope(int $creatorUserId, ?User $viewer): bool
    {
        if (! $viewer || $creatorUserId <= 0) {
            return false;
        }

        $viewerScopeIds = $this->visibleDocumentUnitIds($viewer);
        if ($viewerScopeIds === []) {
            return false;
        }

        return User::query()
            ->whereKey($creatorUserId)
            ->whereHas('protocolUnits', function ($protocolUnitQuery) use ($viewerScopeIds) {
                $protocolUnitQuery
                    ->where('ativo', true)
                    ->whereHas('unit', function ($unitQuery) use ($viewerScopeIds) {
                        $unitQuery
                            ->whereIn('id', $viewerScopeIds)
                            ->orWhereIn('parent_id', $viewerScopeIds);
                    });
            })
            ->exists();
    }

    private function visibleDocumentUnitIds(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return $user->protocolUnits()
            ->where('ativo', true)
            ->with('unit:id,parent_id')
            ->get()
            ->flatMap(function ($link) {
                $ids = [];

                if ($link->protocol_organizational_unit_id) {
                    $ids[] = (int) $link->protocol_organizational_unit_id;
                }

                if ($link->unit?->parent_id) {
                    $ids[] = (int) $link->unit->parent_id;
                }

                return $ids;
            })
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
