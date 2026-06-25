<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessProfile extends Model
{
    protected $fillable = [
        'nome',
        'slug',
        'descricao',
        'ativo',
        'chat_enabled',
        'almoxarifado_create_enabled',
        'almoxarifado_approve_enabled',
        'almoxarifado_deliver_enabled',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'chat_enabled' => 'boolean',
        'almoxarifado_create_enabled' => 'boolean',
        'almoxarifado_approve_enabled' => 'boolean',
        'almoxarifado_deliver_enabled' => 'boolean',
    ];

    public function pages()
    {
        return $this->belongsToMany(SystemPage::class, 'profile_page_permissions');
    }
}
