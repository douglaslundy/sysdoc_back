<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueTreatmentSession extends Model
{
    // Herda a secretaria do pai (sem coluna própria).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('treatment_plan_id', 'queue_treatment_plans', false, ['queue_id', 'queue']));
    }

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
