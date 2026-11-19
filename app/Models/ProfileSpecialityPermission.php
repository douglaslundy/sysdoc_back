<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Acesso de um perfil de acesso a uma especialidade da fila (ver / editar / inserir paciente). */
class ProfileSpecialityPermission extends Model
{
    // Herda a secretaria da especialidade (sem coluna própria).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('speciality_id', 'specialities'));
    }

    protected $fillable = ['access_profile_id', 'speciality_id', 'can_view', 'can_edit', 'can_insert'];

    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
        'can_insert' => 'boolean',
    ];

    public function accessProfile()
    {
        return $this->belongsTo(AccessProfile::class);
    }

    public function speciality()
    {
        return $this->belongsTo(Speciality::class);
    }
}
