<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'mother', 'mother', 'father', 'cns', 'born_date', 'sexo', 'cpf', 'email', 'phone', 'obs', 'active'];

    // public function addresses()
    // {
    //     return $this->hasOne(Addresses::class, 'id_client', 'id');
    // }

    public function addresses()
    {
        return $this->hasOne(Addresses::class, 'id_client')->join('clients', 'clients.id', '=', 'addresses.id_client');
    }

    public function queue()
    {
        return $this->hasMany(Queue::class, 'id_client');
    }
}
