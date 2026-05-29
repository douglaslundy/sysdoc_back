<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEquipeAps extends Model
{
    protected $table = 'user_equipe_aps';

    protected $fillable = ['user_id', 'nu_ine', 'no_equipe'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
