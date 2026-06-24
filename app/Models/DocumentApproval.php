<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'action',
        'status',
        'requested_by',
        'approved_by',
        'signer_user_ids',
        'signer_count',
        'snapshot',
        'notes',
        'approved_at',
    ];

    protected $casts = [
        'signer_user_ids' => 'array',
        'snapshot' => 'array',
        'approved_at' => 'datetime',
        'signer_count' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
