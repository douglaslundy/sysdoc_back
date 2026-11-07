<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Secretaria extends Model
{
    public const SAUDE_SLUG = 'saude';

    // Páginas de gestão (admin/gestor): não dependem da liberação da secretaria.
    public const PAGINAS_GESTAO = ['/users', '/perfis', '/secretarias', '/usuarios/aprovacoes'];

    protected $fillable = ['nome', 'slug', 'ativa'];

    protected $casts = ['ativa' => 'boolean'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_secretarias');
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(SystemPage::class, 'secretaria_pages');
    }
}
