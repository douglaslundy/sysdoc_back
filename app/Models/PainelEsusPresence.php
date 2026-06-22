<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PainelEsusPresence extends Model
{
    use HasFactory;

    protected $table = 'painel_esus_presences';

    protected $fillable = [
        'cnes',
        'panel_name',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];
}
