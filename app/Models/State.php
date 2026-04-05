<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $fillable = ['code', 'name'];
    public $timestamps = false;
   

    // Mutator para sigla (UF)
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = strtoupper($value);
    }

    // Mutator para nome
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtoupper($value);
    }
}
