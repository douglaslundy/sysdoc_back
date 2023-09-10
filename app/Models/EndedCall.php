<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EndedCall extends Model
{
    use HasFactory;

    public function call(){
        return $this->hasOne(Call::class, 'id', 'call_id');
    }
}
