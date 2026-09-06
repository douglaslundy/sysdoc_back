<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueTreatmentSession extends Model
{
    protected $fillable = [
        'treatment_plan_id',
        'scheduled_date',
        'original_scheduled_date',
        'status',
        'reschedule_reason',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'original_scheduled_date' => 'date',
    ];

    public function plan()
    {
        return $this->belongsTo(QueueTreatmentPlan::class, 'treatment_plan_id');
    }
}
