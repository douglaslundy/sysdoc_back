<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    public function addresses()
    {
        return $this->hasOne(Addresses::class, 'id_client', 'id');
    }

    public function queue()
    {
        return $this->hasMany(Queue::class, 'id_client');
    }
}
