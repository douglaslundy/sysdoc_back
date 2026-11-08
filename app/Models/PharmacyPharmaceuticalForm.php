<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyPharmaceuticalForm extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    protected $fillable = ['secretaria_id', 'name', 'active'];
}
