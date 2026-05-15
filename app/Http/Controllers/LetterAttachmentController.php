<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterAttachmentRequest;
use App\Models\Letter;
use App\Models\LetterAttachment;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class LetterAttachmentController extends Controller
{
    private const ATTACHMENT_DISK = 'private';
    private const ATTACHMENT_DIR = 'letter-attachments';

    public function index(Letter $letter): JsonResponse
    {
        $attachments = $letter->attachments()
            ->with('uploader:id,name')
            ->orderByDesc('id')
            ->get();

        return response()->json($attachments);
    }

    public function store(StoreLetterAttachmentRequest $request, Letter $letter): JsonResponse
    {
        $files = $request->hasFile('files') ? $request->file('files') : [$request->file('file')];
        $created = [];

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $path = $file->store(self::ATTACHMENT_DIR . '/' . $letter->id, self::ATTACHMENT_DISK);

            $attachment = LetterAttachment::create([
                'letter_id' => $letter->id,
                'uploaded_by' => $request->user()?->id,
                'disk' => self::ATTACHMENT_DISK,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                'size_bytes' => (int) $file->getSize(),
            ]);

            $created[] = $attachment->load('uploader:id,name');
        }

        AuditService::record('CREATE_ATTACHMENT', $letter, null, [
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

    public function download(Letter $letter, LetterAttachment $attachment)
    {
        if (!$this->belongsToLetter($letter, $attachment)) {
            return response()->json(['message' => 'Anexo nao pertence a este registro.'], 422);
        }

        [$disk, $path] = $this->resolveReadableLocation($attachment);

        if (!$disk || !$path) {
            return response()->json(['message' => 'Arquivo nao encontrado no armazenamento. Verifique se o anexo existe no disco configurado.'], 404);
        }

        AuditService::record('DOWNLOAD_ATTACHMENT', $letter, null, [
            'attachment_id' => $attachment->id,
            'original_name' => $attachment->original_name,
        ]);

        return Storage::disk($disk)->download($path, $this->sanitizeFilename($attachment->original_name));
    }

    public function destroy(Letter $letter, LetterAttachment $attachment): JsonResponse
    {
        if (!$this->belongsToLetter($letter, $attachment)) {
            return response()->json(['message' => 'Anexo nao pertence a este registro.'], 422);
        }

        $old = $attachment->toArray();

        if (Storage::disk($attachment->disk)->exists($attachment->path)) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $attachment->delete();

        AuditService::record('DELETE_ATTACHMENT', $letter, $old, null);

        return response()->json(['message' => 'Anexo removido com sucesso.']);
    }

    private function sanitizeFilename(string $filename): string
    {
        $sanitized = str_replace(['\\', '/'], '-', $filename);
        $sanitized = preg_replace('/[^A-Za-z0-9._\\- ]/', '', $sanitized) ?? 'arquivo';
        $sanitized = trim($sanitized);

        return $sanitized !== '' ? $sanitized : 'arquivo';
    }

    private function resolveReadableLocation(LetterAttachment $attachment): array
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
            if (!$disk || !$path) {
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

    private function belongsToLetter(Letter $letter, LetterAttachment $attachment): bool
    {
        return (int) $attachment->letter_id === (int) $letter->id;
    }
}
