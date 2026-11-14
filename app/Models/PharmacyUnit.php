<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyUnit extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    protected $fillable = ['secretaria_id', 'code', 'name', 'active'];
}
