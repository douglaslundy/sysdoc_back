<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemPage extends Model
{
    protected $fillable = ['titulo', 'path', 'icone', 'categoria', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function profiles()
    {
        return $this->belongsToMany(AccessProfile::class, 'profile_page_permissions');
    }
}
