<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    use HasFactory;

    public function client(){
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
}
