<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFiscalizacaoAttachmentRequest;
use App\Models\Fiscalizacao;
use App\Models\FiscalizacaoAttachment;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FiscalizacaoAttachmentController extends Controller
{
    private const ATTACHMENT_DISK = 'private';

    private const ATTACHMENT_DIR = 'fiscalizacao-attachments';

    public function index(Request $request, Fiscalizacao $fiscalizacao): JsonResponse
    {
        $attachments = $fiscalizacao->attachments()
            ->with('uploader:id,name')
            ->orderByDesc('id')
            ->get();

        return response()->json($attachments);
    }

    public function store(StoreFiscalizacaoAttachmentRequest $request, Fiscalizacao $fiscalizacao): JsonResponse
    {
        $files = $request->hasFile('files')
            ? $request->file('files')
            : [$request->file('file')];

        $created = [];

        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store(self::ATTACHMENT_DIR.'/'.$fiscalizacao->id, self::ATTACHMENT_DISK);

            $attachment = FiscalizacaoAttachment::create([
                'fiscalizacao_id' => $fiscalizacao->id,
                'uploaded_by' => $request->user()?->id,
                'disk' => self::ATTACHMENT_DISK,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                'size_bytes' => (int) $file->getSize(),
            ]);

            $created[] = $attachment->load('uploader:id,name');
        }

        return response()->json([
            'message' => count($created) > 1 ? 'Arquivos enviados com sucesso.' : 'Arquivo enviado com sucesso.',
            'attachments' => $created,
        ], 201);
    }

    public function download(Request $request, Fiscalizacao $fiscalizacao, FiscalizacaoAttachment $attachment)
    {
        if (! $this->belongsToFiscalizacao($fiscalizacao, $attachment)) {
            return response()->json(['message' => 'Anexo não pertence a esta fiscalização.'], 422);
        }

        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            return response()->json(['message' => 'Arquivo não encontrado no armazenamento.'], 404);
        }

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $this->sanitizeFilename($attachment->original_name)
        );
    }

    public function destroy(Request $request, Fiscalizacao $fiscalizacao, FiscalizacaoAttachment $attachment): JsonResponse
    {
        $user = $request->user();
        if (! app(PagePermissionService::class)->canAccess($user, '/fiscalizacoes')) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        if (! $this->belongsToFiscalizacao($fiscalizacao, $attachment)) {
            return response()->json(['message' => 'Anexo não pertence a esta fiscalização.'], 422);
        }

        if (Storage::disk($attachment->disk)->exists($attachment->path)) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $attachment->delete();

        return response()->json(['message' => 'Anexo removido com sucesso.']);
    }

    private function sanitizeFilename(string $filename): string
    {
        $sanitized = str_replace(['\\', '/'], '-', $filename);
        $sanitized = preg_replace('/[^A-Za-z0-9._\\- ]/', '', $sanitized) ?? 'arquivo';
        $sanitized = trim($sanitized);

        return $sanitized !== '' ? $sanitized : 'arquivo';
    }

    private function belongsToFiscalizacao(Fiscalizacao $fiscalizacao, FiscalizacaoAttachment $attachment): bool
    {
        return (int) $attachment->fiscalizacao_id === (int) $fiscalizacao->id;
    }
}
