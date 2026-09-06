<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueTreatmentPlan extends Model
{
    protected $fillable = [
        'queue_id',
        'speciality_id',
        'client_id',
        'created_by_user_id',
        'total_sessions',
        'weekdays',
        'started_at',
        'expected_end_at',
        'status',
        'cancelled_by_user_id',
        'cancelled_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'weekdays' => 'array',
        'started_at' => 'date',
        'expected_end_at' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function queue()
    {
        return $this->belongsTo(Queue::class);
    }

    public function speciality()
    {
        return $this->belongsTo(Speciality::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function sessions()
    {
        return $this->hasMany(QueueTreatmentSession::class, 'treatment_plan_id');
    }
}
