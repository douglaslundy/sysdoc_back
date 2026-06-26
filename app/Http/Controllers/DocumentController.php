<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Authorization\PagePermissionService;
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
            ->with(['type:id,codigo,nome', 'creator:id,name', 'latestVersion.uploader:id,name'])
            ->when(! $this->isAdmin($request->user()), function ($builder) use ($request) {
                $builder->where(function ($q) use ($request) {
                    $q->where('sigilo', 'publico')
                        ->orWhere('created_by', $request->user()?->id);
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

        if (! $document || ! $this->canAccess($document, $request->user())) {
            return response()->json(['message' => 'Documento não encontrado.'], 404);
        }

        return response()->json($document);
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

        return response()->json($document, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);
        if (! $document || ! $this->canAccess($document, $request->user())) {
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

        return response()->json($document->fresh(['type:id,codigo,nome', 'creator:id,name', 'latestVersion.uploader:id,name']));
    }

    public function uploadVersion(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);
        if (! $document || ! $this->canAccess($document, $request->user())) {
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
        if (! $document || ! $this->canAccess($document, $request->user())) {
            return response()->json(['message' => 'Documento não encontrado.'], 404);
        }

        $signers = [];
        if (in_array($document->sigilo, ['interno', 'restrito'], true)) {
            $signers = $this->validateTripleSignature($request);
        }

        DB::transaction(function () use ($request, $document, $signers) {
            $old = $document->toArray();

            if ($signers !== []) {
                DocumentApproval::create([
                    'document_id' => $document->id,
                    'action' => 'delete',
                    'status' => 'approved',
                    'requested_by' => $request->user()?->id,
                    'approved_by' => $request->user()?->id,
                    'signer_user_ids' => $signers,
                    'signer_count' => count($signers),
                    'snapshot' => $old,
                    'approved_at' => now(),
                ]);
            }

            foreach ($document->versions as $version) {
                if ($version->disk && $version->path && Storage::disk($version->disk)->exists($version->path)) {
                    Storage::disk($version->disk)->delete($version->path);
                }
                $version->delete();
            }

            $document->update(['deleted_by' => $request->user()?->id]);
            $document->delete();
            AuditService::record('DELETE', $document, $old, null);
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
            ->orderByDesc('approved_at')
            ->orderByDesc('id');

        if ($request->filled('document_id')) {
            $query->where('document_id', $request->integer('document_id'));
        }

        return response()->json($query->paginate(max(1, min(100, (int) $request->input('per_page', 15)))));
    }

    public function versions(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);
        if (! $document || ! $this->canAccess($document, $request->user())) {
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

        if (! $document || ! $version || (int) $version->document_id !== (int) $document->id || ! $this->canAccess($document, $request->user())) {
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

    private function canAccess(Document $document, ?User $user): bool
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

        return $document->sigilo === 'publico' || (int) $document->created_by === (int) $user->id;
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

    private function validateTripleSignature(Request $request): array
    {
        $validated = $request->validate([
            'assinaturas' => ['required', 'array', 'size:3'],
            'assinaturas.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $signers = array_values(array_unique(array_map('intval', $validated['assinaturas'] ?? [])));
        if (count($signers) !== 3) {
            throw ValidationException::withMessages([
                'assinaturas' => 'A exclusao exige tripla assinatura distinta.',
            ]);
        }

        if (! in_array((int) $request->user()?->id, $signers, true)) {
            throw ValidationException::withMessages([
                'assinaturas' => 'O usuario logado deve compor a tripla assinatura.',
            ]);
        }

        return $signers;
    }

    private function sanitizeFilename(string $filename): string
    {
        $sanitized = str_replace(['\\', '/'], '-', $filename);
        $sanitized = preg_replace('/[^A-Za-z0-9._\\- ]/', '', $sanitized) ?? 'arquivo';
        $sanitized = trim($sanitized);

        return $sanitized !== '' ? $sanitized : 'arquivo';
    }
}
