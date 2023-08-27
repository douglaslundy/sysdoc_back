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

    public function calls_per_room(){
        return $this->hasMany(Call::class, 'room_id', 'id');
    }

    public function calls_per_service(){
        return $this->hasMany(Call::class, 'call_service_id', 'call_service_id');
    }
}
