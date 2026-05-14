<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemPage extends Model
{
    protected $fillable = ['titulo', 'path', 'icone', 'categoria', 'category_id', 'ordem', 'ativo'];

    protected $casts = ['ativo' => 'boolean', 'ordem' => 'integer'];

    public function profiles()
    {
        return $this->belongsToMany(AccessProfile::class, 'profile_page_permissions');
    }

    public function category()
    {
        return $this->belongsTo(PageCategory::class, 'category_id');
    }
}
