<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripScheduleEntry extends Model
{
    use Concerns\BelongsToSecretaria;

    protected $fillable = [
        'secretaria_id', 'scheduled_date', 'client_id', 'person_type', 'companion_of', 'phone',
        'departure_location', 'destination_location', 'time', 'status', 'obs', 'created_by',
    ];

    protected $casts = ['scheduled_date' => 'date:Y-m-d'];

    public function client()
    {
        return $this->belongsTo(Client::class)->select(['id', 'name', 'cns', 'cpf', 'phone']);
    }
}
