<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAtalho extends Model
{
    public const MAXIMO = 9;

    protected $table = 'user_atalhos';

    protected $fillable = ['user_id', 'titulo', 'url', 'icone', 'ordem'];
}
