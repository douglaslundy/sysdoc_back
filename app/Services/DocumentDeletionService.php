<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentDeletionService
{
    public function delete(Document $document, ?int $deletedBy): void
    {
        $document->loadMissing('versions');
        $old = $document->toArray();

        foreach ($document->versions as $version) {
            if ($version->disk && $version->path && Storage::disk($version->disk)->exists($version->path)) {
                Storage::disk($version->disk)->delete($version->path);
            }
            $version->delete();
        }

        $document->update(['deleted_by' => $deletedBy]);
        $document->delete();

        AuditService::record('DELETE', $document, $old, null);
    }
}
