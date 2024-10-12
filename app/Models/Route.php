<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'origin',
        'destination',
        'distance',
    ];

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
