<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentVersion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'document_id',
        'version_number',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'checksum',
        'encrypted',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'size_bytes' => 'integer',
        'encrypted' => 'boolean',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
