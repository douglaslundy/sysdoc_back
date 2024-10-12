<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'brand',
        'model',
        'color',
        'license_plate',
        'renavan',
        'chassis',
        'capacity',
        'year',
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
