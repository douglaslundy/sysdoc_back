<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolMovement extends Model
{
    use HasFactory;

    protected $table = 'protocol_movements';

    protected $fillable = [
        'protocol_id',
        'from_unit_id',
        'to_unit_id',
        'from_user_id',
        'to_user_id',
        'acao',
        'status_anterior',
        'status_novo',
        'observacao',
        'dados',
        'user_id',
    ];

    protected $casts = [
        'dados' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
