<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPresence extends Model
{
    use HasFactory;

    protected $table = 'user_presences';

    protected $fillable = [
        'user_id',
        'last_seen_at',
        'last_path',
        'status',
        'connection_count',
        'connected_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'connected_at' => 'datetime',
        'connection_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
