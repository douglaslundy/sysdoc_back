<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolUserUnit extends Model
{
    use HasFactory;

    protected $table = 'protocol_user_units';

    protected $fillable = [
        'user_id',
        'protocol_organizational_unit_id',
        'papel',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProtocolOrganizationalUnit::class, 'protocol_organizational_unit_id');
    }
}
