<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'origin',
        'origin_state',
        'destination',
        'destination_state',
        'distance',
        'id_user',
    ];

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Relacionamento com a tabela Users
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
