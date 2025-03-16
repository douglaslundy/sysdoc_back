<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripClient extends Model
{
    // Definindo a tabela associada ao modelo
    protected $table = 'trip_clients';

    // Definindo os campos que podem ser preenchidos
    protected $fillable = [
        'trip_id',
        'client_id',
        'person_type',
        'phone',
        'departure_location',
        'destination_location',
        'time',
        'is_confirmed'
    ];

    // Constantes para os possíveis valores do campo 'person_type'
    const PERSON_TYPE_PASSENGER = 'passenger';
    const PERSON_TYPE_COMPANION = 'companion';

    /**
     * Definir o relacionamento com o modelo Trip
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Definir o relacionamento com o modelo Client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
