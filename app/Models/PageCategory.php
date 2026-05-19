<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageCategory extends Model
{
    protected $fillable = ['nome', 'icone', 'ordem', 'ativo'];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function pages()
    {
        return $this->hasMany(SystemPage::class, 'category_id');
    }
}
