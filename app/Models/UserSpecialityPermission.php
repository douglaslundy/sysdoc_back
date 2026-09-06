<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSpecialityPermission extends Model
{
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
