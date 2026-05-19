<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceTicket extends Model
{
    use HasFactory;

    public const STATUS_AGUARDANDO = 'aguardando';
    public const STATUS_CHAMADA = 'chamada';
    public const STATUS_EM_ATENDIMENTO = 'em_atendimento';
    public const STATUS_FINALIZADA = 'finalizada';
    public const STATUS_CANCELADA = 'cancelada';
    public const STATUS_NAO_COMPARECEU = 'nao_compareceu';

    protected $fillable = [
        'number',
        'display_code',
        'sequence_date',
        'client_id',
        'status',
        'issued_at',
        'called_at',
        'started_at',
        'finished_at',
        'cancelled_at',
        'no_show_at',
        'assigned_user_id',
        'room_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'sequence_date' => 'date',
        'issued_at' => 'datetime',
        'called_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'no_show_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function room()
    {
        return $this->belongsTo(AttendanceRoom::class, 'room_id');
    }

    public function calls()
    {
        return $this->hasMany(AttendanceCall::class, 'attendance_ticket_id');
    }

    public function record()
    {
        return $this->hasOne(AttendanceRecord::class, 'attendance_ticket_id');
    }
}

