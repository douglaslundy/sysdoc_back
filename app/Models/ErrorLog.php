<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'file',
        'line',
        'user_id',
        'message',
        'trace',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    // Relacionamento com o modelo User (opcional)
    public function user()
    {
        // return $this->belongsTo(User::class);
        return $this->belongsTo(User::class, 'user_id');
    }
}
