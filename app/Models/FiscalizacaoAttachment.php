<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalizacaoAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiscalizacao_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    public function fiscalizacao()
    {
        return $this->belongsTo(Fiscalizacao::class, 'fiscalizacao_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
