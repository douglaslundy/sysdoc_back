<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'user_id',
        'driver_id',
        'vehicle_id',
        'route_id',
        'departure_time',
        'departure_date',
        'obs',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'trip_clients')->with(['addresses:id_client,street,number,district'])
            // Passageiro primeiro e seus acompanhantes logo abaixo (telas e relatórios herdam esta ordem).
            ->orderByRaw('COALESCE(trip_clients.companion_of, trip_clients.id), trip_clients.companion_of IS NOT NULL, trip_clients.id')
            ->withPivot(['id', 'person_type', 'companion_of', 'phone', 'departure_location',  'destination_location', 'time', 'is_confirmed', 'updated_at']); // Adiciona o campo da tabela intermediária;
    }
}
