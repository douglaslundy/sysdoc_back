<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'status'];

    public function call_service(){
        return $this->hasOne(CallService::class, 'id', 'call_service_id');
    }

    public function calls(){
        return $this->hasMany(Call::class, 'room_id', 'id');
    }
}
