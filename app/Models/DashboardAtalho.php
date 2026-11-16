<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardAtalho extends Model
{
    protected $table = 'dashboard_atalhos';

    protected $fillable = ['titulo', 'url', 'icone', 'ordem', 'ativo'];

    protected $casts = ['ativo' => 'boolean', 'ordem' => 'integer'];
}
