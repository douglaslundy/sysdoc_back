<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicinePublication extends Model
{
    use HasFactory;

    protected $table = 'medicine_publications';

    protected $fillable = [
        'reference_type',
        'reference_id',
        'channel',
        'status',
        'payload_summary',
        'published_at',
        'published_by_user_id',
    ];

    protected $casts = [
        'payload_summary' => 'array',
        'published_at' => 'datetime',
    ];

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }
}
