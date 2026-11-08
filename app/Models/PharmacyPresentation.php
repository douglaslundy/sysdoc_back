<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyPresentation extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    use HasFactory;

    protected $table = 'pharmacy_presentations';

    protected $fillable = [
        'secretaria_id',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
