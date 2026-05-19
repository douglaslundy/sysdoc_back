<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_ticket_id',
        'client_id',
        'user_id',
        'room_id',
        'called_at',
    ];

    protected $casts = [
        'called_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(AttendanceTicket::class, 'attendance_ticket_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function room()
    {
        return $this->belongsTo(AttendanceRoom::class, 'room_id');
    }
}

