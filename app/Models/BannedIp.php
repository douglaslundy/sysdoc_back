<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannedIp extends Model
{
    protected $fillable = ['ip', 'origin', 'reason', 'source', 'hits', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];
}
