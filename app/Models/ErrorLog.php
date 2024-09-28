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
        'message',
        'trace',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];
}
