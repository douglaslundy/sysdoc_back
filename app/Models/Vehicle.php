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
        'year'
    ];

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
