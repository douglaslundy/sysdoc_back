<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OuvidoriaAnexo extends Model
{
    protected $table = 'ouvidoria_anexos';

    protected $fillable = ['manifestacao_id', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes'];

    protected $hidden = ['disk', 'path'];
}
