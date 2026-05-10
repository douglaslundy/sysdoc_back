<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabConfig extends Model
{
    protected $fillable = ['email_habilitado'];
    protected $casts = ['email_habilitado' => 'boolean'];

    // Retorna a única config existente (singleton)
    public static function get(): self
    {
        return static::firstOrCreate([], ['email_habilitado' => false]);
    }
}
