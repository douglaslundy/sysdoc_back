<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolComment extends Model
{
    use HasFactory;

    protected $table = 'protocol_comments';

    protected $fillable = [
        'protocol_id',
        'user_id',
        'tipo',
        'conteudo',
        'privado',
    ];

    protected $casts = [
        'privado' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
