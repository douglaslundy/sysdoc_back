<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        return $this->belongsToMany(Client::class, 'trip_clients')->with(['addresses:id_client,street,number'])
            ->withPivot(['id', 'person_type', 'phone', 'departure_location',  'destination_location', 'time', 'is_confirmed', 'updated_at']); // Adiciona o campo da tabela intermediária;
    }

}
