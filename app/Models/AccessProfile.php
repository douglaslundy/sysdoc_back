<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessProfile extends Model
{
    protected $fillable = ['nome', 'slug', 'descricao', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function pages()
    {
        return $this->belongsToMany(SystemPage::class, 'profile_page_permissions');
    }
}
