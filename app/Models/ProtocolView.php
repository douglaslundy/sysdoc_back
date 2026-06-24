<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolView extends Model
{
    use HasFactory;

    protected $table = 'protocol_views';

    protected $fillable = [
        'protocol_id',
        'user_id',
        'departamento',
        'equipe',
        'visualized_at',
    ];

    protected $casts = [
        'visualized_at' => 'datetime',
    ];

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocol::class, 'protocol_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
