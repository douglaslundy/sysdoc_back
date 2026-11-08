<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueAttachment extends Model
{
    // Herda a secretaria do pai (sem coluna própria).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('queue_id', 'queue'));
    }

    use HasFactory;

    protected $fillable = [
        'queue_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    public function queue()
    {
        return $this->belongsTo(Queue::class, 'queue_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
