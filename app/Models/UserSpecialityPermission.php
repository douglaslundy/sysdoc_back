<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSpecialityPermission extends Model
{
    // Herda a secretaria do pai (sem coluna própria).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('speciality_id', 'specialities'));
    }

    protected $fillable = [
        'user_id',
        'speciality_id',
        'can_view',
        'can_edit',
        'can_insert',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
        'can_insert' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function speciality()
    {
        return $this->belongsTo(Speciality::class);
    }
}
