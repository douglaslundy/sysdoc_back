<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OsFoto extends Model
{
    protected $table = 'os_fotos';

    protected $fillable = ['ordem_servico_id', 'fase', 'enviada_por', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes'];

    protected $hidden = ['disk', 'path'];
}
