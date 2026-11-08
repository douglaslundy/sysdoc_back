<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRoom extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;
    use HasFactory;

    protected $fillable = [
        'secretaria_id',
        'name',
        'description',
        'active',
    ];

    public function tickets()
    {
        return $this->hasMany(AttendanceTicket::class, 'room_id');
    }
}
