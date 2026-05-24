<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQueueAttachmentRequest;
use App\Models\Queue;
use App\Models\QueueAttachment;
use App\Services\AuditService;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QueueAttachmentController extends Controller
{
    private const ATTACHMENT_DISK = 'private';

    private const ATTACHMENT_DIR = 'queue-attachments';

    public function index(Request $request, Queue $queue): JsonResponse
    {
        if (! $this->canAccessQueue($request)) {
            return response()->json(['message' => 'Voce nao possui permissao para executar esta acao.'], 403);
        }

        $attachments = $queue->attachments()
            ->with('uploader:id,name')
            ->orderByDesc('id')
            ->get();

        return response()->json($attachments);
    }

    public function store(StoreQueueAttachmentRequest $request, Queue $queue): JsonResponse
    {
        $files = $request->hasFile('files')
            ? $request->file('files')
            : [$request->file('file')];

        $created = [];

        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store(self::ATTACHMENT_DIR.'/'.$queue->id, self::ATTACHMENT_DISK);

            $attachment = QueueAttachment::create([
                'queue_id' => $queue->id,
                'uploaded_by' => $request->user()?->id,
                'disk' => self::ATTACHMENT_DISK,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                'size_bytes' => (int) $file->getSize(),
            ]);

            $created[] = $attachment->load('uploader:id,name');
        }

        AuditService::record('CREATE_ATTACHMENT', $queue, null, [
            'count' => count($created),
            'attachments' => collect($created)->map(fn ($item) => [
                'attachment_id' => $item->id,
                'original_name' => $item->original_name,
                'mime_type' => $item->mime_type,
                'size_bytes' => $item->size_bytes,
            ])->values()->all(),
        ]);

        return response()->json([
            'message' => count($created) > 1 ? 'Arquivos enviados com sucesso.' : 'Arquivo enviado com sucesso.',
            'attachments' => $created,
        ], 201);
    }

    public function download(Request $request, Queue $queue, QueueAttachment $attachment)
    {
        if (! $this->canAccessQueue($request)) {
            return response()->json(['message' => 'Voce nao possui permissao para executar esta acao.'], 403);
        }

        if (! $this->belongsToQueue($queue, $attachment)) {
            return response()->json(['message' => 'Anexo nao pertence a este registro.'], 422);
        }

        [$disk, $path] = $this->resolveReadableLocation($attachment);

        if (! $disk || ! $path) {
            return response()->json([
                'message' => 'Arquivo nao encontrado no armazenamento. Verifique se o anexo existe no disco configurado.',
            ], 404);
        }

        AuditService::record('DOWNLOAD_ATTACHMENT', $queue, null, [
            'attachment_id' => $attachment->id,
            'original_name' => $attachment->original_name,
        ]);

        return Storage::disk($disk)->download(
            $path,
            $this->sanitizeFilename($attachment->original_name)
        );
    }

    public function destroy(Request $request, Queue $queue, QueueAttachment $attachment): JsonResponse
    {
        if (! $this->canAccessQueue($request)) {
            return response()->json(['message' => 'Voce nao possui permissao para executar esta acao.'], 403);
        }

        if (! $this->belongsToQueue($queue, $attachment)) {
            return response()->json(['message' => 'Anexo nao pertence a este registro.'], 422);
        }

        $old = $attachment->toArray();

        if (Storage::disk($attachment->disk)->exists($attachment->path)) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $attachment->delete();

        AuditService::record('DELETE_ATTACHMENT', $queue, $old, null);

        return response()->json(['message' => 'Anexo removido com sucesso.']);
    }

    private function sanitizeFilename(string $filename): string
    {
        $sanitized = str_replace(['\\', '/'], '-', $filename);
        $sanitized = preg_replace('/[^A-Za-z0-9._\\- ]/', '', $sanitized) ?? 'arquivo';
        $sanitized = trim($sanitized);

        return $sanitized !== '' ? $sanitized : 'arquivo';
    }

    private function resolveReadableLocation(QueueAttachment $attachment): array
    {
        $candidates = [];

        if ($attachment->disk && $attachment->path) {
            $candidates[] = [$attachment->disk, $attachment->path];
        }

        if ($attachment->path) {
            $normalizedPath = ltrim(str_replace('\\', '/', $attachment->path), '/');
            $candidates[] = ['private', $normalizedPath];
            $candidates[] = ['public', $normalizedPath];
        }

        foreach ($candidates as [$disk, $path]) {
            if (! $disk || ! $path) {
                continue;
            }

            try {
                if (Storage::disk($disk)->exists($path)) {
                    return [$disk, $path];
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return [null, null];
    }

    private function belongsToQueue(Queue $queue, QueueAttachment $attachment): bool
    {
        return (int) $attachment->queue_id === (int) $queue->id;
    }

    private function canAccessQueue(Request $request): bool
    {
        $user = $request->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/queue');
    }
}
